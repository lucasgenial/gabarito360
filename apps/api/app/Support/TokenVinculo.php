<?php
namespace App\Support;

use App\Models\Usuario;
use Laravel\Sanctum\PersonalAccessToken;

/** Metadados de contexto escritos apenas pelo servidor; nunca poderes de negócio. */
class TokenVinculo
{
    public const NOME = 'vinculo-contexto';

    public static function identidade(Usuario $u): PersonalAccessToken
    {
        $t = $u->currentAccessToken();
        abort_unless($t instanceof PersonalAccessToken && !in_array($t->name, [self::NOME, TokenInterno::NOME], true)
            && (int) $t->tokenable_id === (int) $u->id && $t->tokenable_type === $u->getMorphClass(), 403);
        abort_unless($u->ativo && (!$t->expires_at || $t->expires_at->isFuture()), 403);
        return $t;
    }

    public static function resolver(Usuario $u): Usuario
    {
        $t = $u->currentAccessToken();
        if (!$t instanceof PersonalAccessToken || $t->name !== self::NOME) return $u;
        $a = $t->abilities;
        abort_unless(is_array($a) && count($a) === 2
            && preg_match('/^vinculo:([1-9][0-9]*)$/D', $a[0] ?? '', $v)
            && preg_match('/^identidade:([1-9][0-9]*)$/D', $a[1] ?? '', $i), 403, 'Contexto inválido.');
        $pai = PersonalAccessToken::find($i[1]);
        abort_unless($pai && !in_array($pai->name, [self::NOME, TokenInterno::NOME], true) && $pai->tokenable_type === $u->getMorphClass()
            && (int) $pai->tokenable_id === (int) $u->id
            && (!$pai->expires_at || $pai->expires_at->isFuture())
            && (!config('sanctum.expiration') || $pai->created_at->gt(now()->subMinutes(config('sanctum.expiration')))), 403, 'Sessão de origem encerrada.');
        return $u->noVinculo((int) $v[1])->withAccessToken($t);
    }
}
