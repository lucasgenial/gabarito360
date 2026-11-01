<?php

namespace App\Support;

/** Contrato T3; compatibilidade de perfil não substitui autorização nem habilitação. */
final class PerfisEspaco
{
    public const POR_TIPO = [
        'pessoal' => ['professor_autonomo'],
        'escola' => ['dir_escolar', 'coordenador', 'professor', 'aplicador', 'aluno'],
        'nucleo' => ['dir_nucleo'],
        'rede' => ['admin_rede'],
    ];

    public static function tipo(string $perfil): ?string
    {
        foreach (self::POR_TIPO as $tipo => $perfis) {
            if (in_array($perfil, $perfis, true)) return $tipo;
        }
        return null;
    }

    public static function compativel(string $perfil, object $espaco, int $usuario): bool
    {
        return $usuario > 0 && self::tipo($perfil) !== null && self::tipo($perfil) === $espaco->tipo
            && ($espaco->tipo !== 'pessoal' || (int) ($espaco->usuario_proprietario_id ?? 0) === $usuario);
    }

    /** Catálogo já preparado, mas consumidores pessoais ainda não estão integrados. */
    public static function habilitado(string $perfil): bool
    {
        return in_array(self::tipo($perfil), ['rede', 'nucleo', 'escola'], true)
            || ($perfil === 'professor_autonomo' && config('espacos.pessoal', false));
    }

    /** Portal futuro: nunca inferir habilitação pelo pagador ou por permissão extra. */
    public static function admitePortalPais(string $tipo): bool
    {
        return $tipo === 'escola';
    }
}
