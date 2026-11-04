<?php
namespace App\Support;

final class PerfisEscolares
{
    public const INSTITUCIONAIS = ['coordenador', 'dir_escolar', 'vice_dir_escolar'];
    public const ADMINISTRATIVOS = ['secretario_escolar', 'auxiliar_secretaria'];

    public static function administrativo(?string $perfil): bool
    {
        return in_array($perfil, self::ADMINISTRATIVOS, true);
    }
}
