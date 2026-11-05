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
    $html = view('home.index')->render();
    $contrato=g360_publicos_contrato($base.'/public');
    $html=g360_publicos_html($html,$contrato,$url);
    $texto=html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8');
    preg_match_all('~<meta property="(og:[^"]+)" content="([^"]*)"~', $html, $encontrados, PREG_SET_ORDER);
    $og=[];
    foreach ($encontrados as $meta) $og[$meta[1]][]=html_entity_decode($meta[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
    foreach (['og:title','og:type','og:url','og:image'] as $campo) {
        if (count($og[$campo]??[])!==1 || !$og[$campo][0]) throw new RuntimeException('Prévia social incompleta.');
    }
    if ($og['og:url'][0]!==$url.'/' || $og['og:type'][0]!=='website'
        || $og['og:image'][0]!==$url.$contrato['prefixo'].'brand/gabarito360-og.png'
        || !str_contains($html,'<link rel="canonical" href="'.$url.'/">')) {
        throw new RuntimeException('Origem pública da prévia social divergente.');
    }
    // Não congelar formulários, tokens, placeholders Blade ou contexto autenticado.
    if (preg_match('/R\$|<form\b|csrf|name=["\x27]_token|g360-contexto|<x-marca|@csrf/i', $html)
        || preg_match('/Previsto no planejamento|\bRoadmap\b|Em desenvolvimento|\bPlanejado\b/iu',$texto)
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
