<?php
// Auditoria de layout: renderiza as telas de cada perfil com as respostas reais da API (gravadas por aud_api.php).
// Nada é gravado no banco. Saída: /tmp/aud/<perfil>__<rota>.html
use Illuminate\Support\Facades\Http;
$cfg = json_decode(file_get_contents('/tmp/aud_cfg.json'), true);
@mkdir('/tmp/aud');
$miss = [];
foreach ($cfg as $chave => $p) {
    $fake = is_file("/tmp/aud_fake_{$chave}.json") ? json_decode(file_get_contents("/tmp/aud_fake_{$chave}.json"), true) : [];
    $faltam = [];
    Http::swap(new Illuminate\Http\Client\Factory);
    Http::fake(function ($req) use ($fake, &$faltam) {
        $pu = parse_url($req->url()); $uri = $pu['path'] . (isset($pu['query']) ? '?' . $pu['query'] : '');
        if (!isset($fake[$uri])) { $faltam[$uri] = true; return Http::response(['message' => 'sem fake'], 404); }
        return Http::response($fake[$uri]['body'], $fake[$uri]['status']);
    });
    app('session')->flush();
    if ($p['uid']) {
        session(['auth_token' => 'x', 'auth_perfil' => $p['perfil'], 'auth_permissoes' => $fake['__permissoes'] ?? [],
                 'auth_nome' => $p['nome'], 'auth_email' => $p['perfil'] . '@gabarito360.dev']);
    }
    view()->share('errors', new \Illuminate\Support\ViewErrorBag);
    foreach ($p['paginas'] as [$nome, $vals]) {
        $rota = app('router')->getRoutes()->getByName($nome);
        app('request')->setRouteResolver(fn () => $rota);
        if ($nome === 'referencia') { file_put_contents("/tmp/aud/{$chave}__{$nome}.html", view('referencia.index')->render()); continue; }
        [$cls, $met] = explode('@', $rota->getActionName());
        $par = [];
        foreach ((new ReflectionMethod($cls, $met))->getParameters() as $rp) {
            $t = $rp->getType();
            if ($t instanceof ReflectionNamedType && !$t->isBuiltin()) continue;
            if ($vals) $par[$rp->getName()] = array_shift($vals);
        }
        try {
            $r = app()->call($rota->getActionName(), $par);
            $html = $r instanceof Illuminate\Contracts\View\View ? $r->render() : (method_exists($r, 'getContent') ? (string) $r->getContent() : '');
            file_put_contents("/tmp/aud/{$chave}__{$nome}.html", $html);
        } catch (Throwable $e) {
            echo "{$chave} {$nome}: ERRO " . class_basename($e) . ' ' . mb_substr($e->getMessage(), 0, 140) . "\n";
        }
    }
    $miss[$chave] = array_keys($faltam);
}
file_put_contents('/tmp/aud_miss.json', json_encode($miss));
echo 'faltando: ' . array_sum(array_map('count', $miss)) . "\n";
