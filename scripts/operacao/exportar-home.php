<?php
/** Renderização anônima para publicação estática; executável pelo www-data. */
require __DIR__.'/assets-publicos.php';
try {
    $base = realpath(($argv[1] ?? '').'/apps/web');
    if (!$base) throw new RuntimeException('Aplicação ausente.');
    require $base.'/vendor/autoload.php';
    $app = require $base.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $url = g360_publicos_origem($argv[2] ?? config('app.url'));
    $request = Illuminate\Http\Request::create($url.'/', 'GET');
    $request->setRouteResolver(fn () => Illuminate\Support\Facades\Route::getRoutes()->getByName('home'));
    $app->instance('request', $request);
    Illuminate\Support\Facades\URL::forceRootUrl($url);
    Illuminate\Support\Facades\URL::forceScheme(parse_url($url, PHP_URL_SCHEME));
    $resposta = $app->make(App\Services\ApiClient::class)->get('/v1/planos');
    if (!$resposta->successful() || !is_array($resposta->json('data'))) {
        throw new RuntimeException('Não substituir conteúdo público por fallback de indisponibilidade.');
    }
    $planos = array_values(array_filter($resposta->json('data'),
        fn ($plano) => ($plano['modo'] ?? null) === 'autoatendimento' && ($plano['ativo'] ?? true)));
    if (!$planos) throw new RuntimeException('Planos públicos exigem conferência antes da exportação.');
    $html = view('home.index', compact('planos'))->render();
    $contrato=g360_publicos_contrato($base.'/public');
    $html=g360_publicos_html($html,$contrato,$url);
    // Não congelar formulários, tokens, placeholders Blade ou contexto autenticado.
    if (preg_match('/<form\b|csrf|name=["\x27]_token|g360-contexto|<x-marca|@csrf/i', $html)
        || !str_contains($html, 'tela-inicio') || !str_contains($html, '</html>')
        || !str_contains($html,$contrato['prefixo'].'css/app.css')
        || !str_contains($html,$contrato['prefixo'].'css/marca.css')) {
        throw new RuntimeException('A home deixou de ser elegível para exportação pública.');
    }
    echo $html;
} catch (Throwable $erro) {
    fwrite(STDERR, "Exportação pública recusada; versão anterior preservada.\n");
    exit(1);
}
