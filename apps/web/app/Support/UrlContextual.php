<?php
namespace App\Support;

/** URLs de navegação contêm somente o identificador público de negócio do acesso. */
final class UrlContextual
{
    public static function contextual(string $caminho): bool
    {
        return $caminho !== '/' && !preg_match('~^/(?:acessos/|api/|login(?:/|$)|logout(?:/|$)|vinculos(?:/|$)|trocar-senha(?:/|$)|fale-conosco(?:/|$)|planos(?:/|$)|checkout(?:/|$)|convite(?:/|$)|confirmar-email(?:/|$)|redefinir-senha(?:/|$)|esqueci-senha(?:/|$)|validar-documento(?:/|$)|health(?:/|$)|up(?:/|$))~', $caminho);
    }

    public static function caminho(string $caminho, string $acesso): string
    {
        return '/acessos/'.$acesso.'/'.ltrim($caminho, '/');
    }

    public static function redirecionamento(string $url, string $acesso): string
    {
        $p = parse_url($url);
        if ($p === false || (isset($p['scheme']) && !in_array(strtolower($p['scheme']), ['http','https'], true))) return $url;
        if (isset($p['host']) && (strtolower($p['host']) !== strtolower(request()->getHost()) || (isset($p['port']) && $p['port'] !== request()->getPort()))) return $url;
        $path = $p['path'] ?? '/';
        parse_str($p['query'] ?? '', $q);
        unset($q['_ctx']);
        if (self::contextual($path)) $path = self::caminho($path, $acesso);
        $origem = isset($p['host']) ? (isset($p['scheme']) ? $p['scheme'].':' : '').'//'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : '') : '';
        return $origem.$path.($q ? '?'.http_build_query($q) : '').(isset($p['fragment']) ? '#'.$p['fragment'] : '');
    }
}
