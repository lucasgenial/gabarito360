<?php
namespace App\Support;

/** Navegação estável: vínculo e credenciais nunca são escolhidos pelo endereço. */
final class UrlContextual
{
    public static function contextual(string $caminho): bool
    {
        return $caminho !== '/' && !preg_match('~^/(?:app(?:/|$)|api/|login(?:/|$)|logout(?:/|$)|fale-conosco(?:/|$)|planos(?:/|$)|checkout(?:/|$)|convite(?:/|$)|confirmar-email(?:/|$)|redefinir-senha(?:/|$)|esqueci-senha(?:/|$)|validar-documento(?:/|$)|health(?:/|$)|up(?:/|$))~', $caminho);
    }

    public static function caminho(string $caminho, ?string $ignorado = null): string
    {
        $caminho = preg_replace('~^/acessos/[^/]+(?=/|$)~', '', $caminho);
        return str_starts_with($caminho, '/app/') ? $caminho : '/app/'.ltrim($caminho, '/');
    }

    public static function redirecionamento(string $url, ?string $ignorado = null): string
    {
        $p = parse_url($url);
        if ($p === false || (isset($p['scheme']) && !in_array(strtolower($p['scheme']), ['http','https'], true))) return $url;
        if (isset($p['host']) && (strtolower($p['host']) !== strtolower(request()->getHost()) || (isset($p['port']) && $p['port'] !== request()->getPort()))) return $url;
        $path = $p['path'] ?? '/';
        parse_str($p['query'] ?? '', $q);
        unset($q['_ctx'], $q['_revisao']);
        if (self::contextual($path)) $path = self::caminho($path);
        $origem = isset($p['host']) ? (isset($p['scheme']) ? $p['scheme'].':' : '').'//'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : '') : '';
        return $origem.$path.($q ? '?'.http_build_query($q) : '').(isset($p['fragment']) ? '#'.$p['fragment'] : '');
    }
}
