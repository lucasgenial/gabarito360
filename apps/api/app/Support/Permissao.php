<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Support/Permissao.php
 * Função: Motor de permissões (docs/03 v2 e docs/21 etapa 2).
 *         Efetivas = padrão do perfil + extras concedidas (válidas) − padrões retirados.
 *         O escopo continua em App\Support\Escopo: permissão nunca amplia o escopo.
 */
class Permissao
{
    /** Cache por requisição: [usuario_id => [chaves]] */
    private static array $cache = [];

    public static function efetivas(Usuario $u): array
    {
        if (ContextoInterno::presente($u)) return ContextoInterno::permissoes($u);
        // Gravar um perfil interno no campo legado não concede acesso comercial ou pedagógico.
        if (in_array($u->getRawOriginal('perfil'), ContextoInterno::PERFIS, true) && !ContextoVinculo::presente($u)) return [];
        RegimeVinculos::exigir($u);
        // Contextos explícitos são revalidados; não reutilizam o cache global legado por pessoa.
        if (ContextoVinculo::presente($u)) return ContextoVinculo::permissoes($u);
        // Estes perfis exigem vínculo explícito, mesmo que recebam extras no cadastro legado.
        if (in_array($u->perfil, ['professor_autonomo', 'secretario_escolar'], true)) return [];
        if (isset(self::$cache[$u->id])) {
            return self::$cache[$u->id];
        }

        $padrao = DB::table('perfil_permissoes')->where('perfil', $u->perfil)->where('tipo', 'padrao')->pluck('permissao')->all();
        $pessoais = DB::table('usuario_permissoes')->where('usuario_id', $u->id)
            ->where(fn ($q) => $q->whereNull('valida_ate')->orWhere('valida_ate', '>=', now()->toDateString()))
            ->get(['permissao', 'efeito']);

        $efetivas = array_diff(
            array_unique(array_merge($padrao, $pessoais->where('efeito', 'conceder')->pluck('permissao')->all())),
            $pessoais->where('efeito', 'retirar')->pluck('permissao')->all()
        );
        sort($efetivas);

        return self::$cache[$u->id] = array_values($efetivas);
    }

    public static function pode(Usuario $u, string $chave): bool
    {
        return in_array($chave, self::efetivas($u), true);
    }

    /** "padrao", "extra" ou null (nunca) para o perfil. */
    public static function tipoNoPerfil(string $perfil, string $chave): ?string
    {
        return DB::table('perfil_permissoes')->where('perfil', $perfil)->where('permissao', $chave)->value('tipo');
    }

    /**
     * Pode o gestor conceder (extra) ou retirar (padrão) esta permissão desta pessoa?
     * Regras (docs/03 §1): o gestor tem a permissão e `usuario.permissoes`, gere o perfil
     * da pessoa (Cadastro) — o escopo da pessoa é conferido por quem chama.
     */
    public static function podeDefinir(Usuario $gestor, Usuario $alvo, string $chave, string $efeito): ?string
    {
        if (!self::pode($gestor, 'usuario.permissoes')) {
            return 'Você não pode conceder ou retirar permissões.';
        }
        if (!Cadastro::podeGerirPerfil($gestor, $alvo->perfil)) {
            return 'Você não gere usuários deste perfil.';
        }
        if (!self::pode($gestor, $chave)) {
            return "Você não pode conceder \"{$chave}\" porque não tem essa permissão.";
        }
        $tipo = self::tipoNoPerfil($alvo->perfil, $chave);
        if ($efeito === 'conceder' && $tipo !== 'extra') {
            return $tipo === 'padrao'
                ? "\"{$chave}\" já é padrão deste perfil."
                : "\"{$chave}\" não pode ser concedida a este perfil.";
        }
        if ($efeito === 'retirar' && $tipo !== 'padrao') {
            return "\"{$chave}\" não é padrão deste perfil para ser retirada.";
        }

        return null;
    }

    public static function limparCache(): void
    {
        self::$cache = [];
    }
}
