<?php
namespace App\Support;

use App\Models\Usuario;
use Laravel\Sanctum\PersonalAccessToken;

final class TokenInterno
{
    public const NOME = 'interno-contexto';

    public static function habilitado(): bool
    {
        return config('acessos_internos.ativo', false) && RegimeVinculos::obrigatorio();
    }

    public static function resolver(Usuario $u): Usuario
    {
        $t = $u->currentAccessToken();
        if (!$t instanceof PersonalAccessToken || $t->name !== self::NOME) return $u;
        abort_unless(self::habilitado(), 403, 'Acesso interno indisponível.');
        $a = $t->abilities;
        abort_unless(is_array($a) && count($a) === 3
            && preg_match('/^interno:([1-9][0-9]*)$/D', $a[0] ?? '', $id)
            && preg_match('/^versao:([1-9][0-9]*)$/D', $a[1] ?? '', $versao)
            && preg_match('/^identidade:([1-9][0-9]*)$/D', $a[2] ?? '', $identidade), 403, 'Contexto inválido.');
        $pai = PersonalAccessToken::find($identidade[1]);
        abort_unless($pai && !in_array($pai->name, [self::NOME, TokenVinculo::NOME], true)
            && $pai->tokenable_type === $u->getMorphClass() && (int) $pai->tokenable_id === (int) $u->id
            && (!$pai->expires_at || $pai->expires_at->isFuture())
            && (!config('sanctum.expiration') || $pai->created_at->gt(now()->subMinutes(config('sanctum.expiration')))), 403, 'Sessão de origem encerrada.');
        return $u->noAcessoInterno((int) $id[1], (int) $versao[1])->withAccessToken($t);
    }
}
