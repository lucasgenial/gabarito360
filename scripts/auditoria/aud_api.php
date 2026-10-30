<?php
// Auditoria de layout, só leitura: grava as respostas da API pedidas pelo web, como cada usuário.
use Illuminate\Http\Request;
$k = app(Illuminate\Contracts\Http\Kernel::class);
$cfg = json_decode(file_get_contents('/tmp/aud_cfg.json'), true);
$miss = is_file('/tmp/aud_miss.json') ? json_decode(file_get_contents('/tmp/aud_miss.json'), true) : [];
foreach ($cfg as $chave => $p) {
    $arq = "/tmp/aud_fake_{$chave}.json";
    $fake = is_file($arq) ? json_decode(file_get_contents($arq), true) : [];
    $u = $p['uid'] ? App\Models\Usuario::find($p['uid']) : null;
    $fake['__permissoes'] = $u ? App\Support\Permissao::efetivas($u) : [];
    foreach ($miss[$chave] ?? [] as $uri) {
        if (!str_contains($uri, '/api/')) continue;
        App\Support\Permissao::limparCache();
        auth()->forgetGuards(); if ($u) { auth()->guard('sanctum')->setUser($u); auth()->shouldUse('sanctum'); }
        $r = $k->handle(Request::create($uri, 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']));
        $fake[$uri] = ['status' => $r->getStatusCode(), 'body' => json_decode($r->getContent(), true)];
    }
    file_put_contents($arq, json_encode($fake));
}
echo "api ok\n";
