<?php

namespace App\Support;

/**
 * URLs de arquivos técnicos públicos versionadas pelo conteúdo (F3, docs/198).
 *
 * A versão muda somente quando os bytes mudam, o que permite cache imutável
 * no servidor. Não use para fotos, documentos ou qualquer arquivo protegido.
 */
final class Ativos
{
    /** @var array<string, string> versões já calculadas nesta requisição */
    private static array $versoes = [];

    public static function url(string $caminho): string
    {
        $caminho = ltrim($caminho, '/');
        $versao = self::versao($caminho);

        return asset($caminho).($versao === null ? '' : '?v='.$versao);
    }

    /** Primeiros 16 hexadecimais do xxh128 do arquivo, ou null se ele não existir. */
    public static function versao(string $caminho): ?string
    {
        $caminho = ltrim($caminho, '/');
        if (str_contains($caminho, '..')) {
            return null;
        }
        if (array_key_exists($caminho, self::$versoes)) {
            return self::$versoes[$caminho];
        }
        $arquivo = public_path($caminho);
        $hash = is_file($arquivo) ? hash_file('xxh128', $arquivo) : false;

        return self::$versoes[$caminho] = $hash === false ? null : substr($hash, 0, 16);
    }

    public static function limpar(): void
    {
        self::$versoes = [];
    }
}
