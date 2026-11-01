<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/** Autorização de plataforma, independente de cliente pagador e vínculo escolar. */
final class ContextoInterno
{
    public const PERFIS = ['desenvolvedor', 'suporte', 'financeiro'];
    private const PERMISSOES = [
        'desenvolvedor' => ['cliente.ver', 'cliente.gerir', 'acesso_interno.gerir'],
        'suporte' => ['cliente.ver', 'cliente.gerir'],
        'financeiro' => ['cliente.ver', 'cliente.gerir'],
    ];

    public static function presente(Usuario $u): bool
    {
        return $u->acessoInternoContextoId() !== null;
    }

    public static function resolver(Usuario $u): object
    {
        abort_unless(config('acessos_internos.ativo', false) && self::presente($u)
            && $u->acessoInternoContextoVersao() > 0
            && !ContextoVinculo::presente($u), 403, 'Acesso interno indisponível.');
        $a = DB::table('usuario_acessos_internos as a')->join('usuarios as u', 'u.id', '=', 'a.usuario_id')
            ->where('a.id', $u->acessoInternoContextoId())->where('a.usuario_id', $u->id)
            ->where('u.ativo', true)->where('a.situacao', 'ativo')
            ->whereIn('a.perfil', self::PERFIS)->where('a.versao_autorizacao', $u->acessoInternoContextoVersao())
            ->where('a.inicio_em', '<=', now())
            ->where(fn ($q) => $q->whereNull('a.fim_em')->orWhere('a.fim_em', '>', now()))
            ->select('a.*')->first();
        abort_unless($a, 403, 'Acesso interno indisponível.');
        return $a;
    }

    public static function permissoes(Usuario $u): array
    {
        return self::PERMISSOES[self::resolver($u)->perfil];
    }

    public static function pode(Usuario $u, string $permissao): bool
    {
        return self::presente($u) && in_array($permissao, self::permissoes($u), true);
    }

    /** Não permite gerir pares, elevar a si mesmo ou conceder Desenvolvedor. */
    public static function podeGerirPerfil(Usuario $u, string $perfil): bool
    {
        return self::pode($u, 'acesso_interno.gerir') && in_array($perfil, ['suporte', 'financeiro'], true);
    }
}
