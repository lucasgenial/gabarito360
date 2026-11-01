<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/** Núcleo V1-C1: contexto explícito no objeto, nunca em estado global ou no perfil persistido. */
class ContextoVinculo
{
    public static function presente(Usuario $u): bool
    {
        return $u->vinculoContextoId() !== null;
    }

    /** Revalida a cada uso; workers não podem guardar uma concessão após revogação. */
    public static function resolver(Usuario $u): object
    {
        abort_unless(Espacos::ativo() && self::presente($u), 403, 'Vínculo indisponível.');
        $v = DB::table('usuario_vinculos as v')->join('usuarios as u', 'u.id', '=', 'v.usuario_id')
            ->where('v.id', $u->vinculoContextoId())->where('v.usuario_id', $u->id)->where('u.ativo', true)
            ->where('v.situacao', 'ativo')->where('v.inicio_em', '<=', now())
            ->where(fn ($q) => $q->whereNull('v.fim_em')->orWhere('v.fim_em', '>', now()))->select('v.*')->first();
        abort_unless($v, 403, 'Vínculo indisponível.');
        $e = DB::table('espacos')->find($v->espaco_id);
        abort_unless($e && PerfisEspaco::habilitado($v->perfil)
            && PerfisEspaco::compativel($v->perfil, $e, (int) $u->id)
            && self::espacoValido($e), 403, 'Vínculo indisponível.');
        $v->espaco = $e;
        return $v;
    }

    public static function espacoValido(object $e): bool
    {
        if (!in_array($e->tipo, ['rede', 'nucleo', 'escola', 'pessoal'], true) || $e->situacao !== 'ativo') return false;
        $referencia = $e->tipo === 'pessoal' ? 'usuario_proprietario_id' : $e->tipo.'_id';
        foreach (['rede_id', 'nucleo_id', 'escola_id', 'usuario_proprietario_id'] as $campo) {
            if (($campo === $referencia) !== ($e->$campo !== null)) return false;
        }
        if (!DB::table('clientes')->where('id', $e->cliente_id)->where('situacao', 'ativo')->exists()) return false;
        if ($e->tipo === 'pessoal') return DB::table('usuarios')->where('id', $e->usuario_proprietario_id)->where('ativo', true)->exists();
        $tabela = match ($e->tipo) { 'rede' => 'redes', 'nucleo' => 'nucleos', 'escola' => 'escolas' };
        $instituicao = DB::table($tabela)->find($e->{$e->tipo.'_id'});
        if (!$instituicao) return false;
        if ($e->tipo === 'rede') return true;
        $tipoPai = $e->tipo === 'escola' ? 'nucleo' : 'rede';
        $pai = DB::table('espacos')->where('tipo', $tipoPai)->where($tipoPai.'_id', $instituicao->{$tipoPai.'_id'})->first();
        return $pai && (int) $pai->cliente_id === (int) $e->cliente_id && self::espacoValido($pai);
    }

    public static function referencias(Usuario $u, string $tipo): array
    {
        $v = self::resolver($u);
        if ($tipo === $v->espaco->tipo) return [(int) $v->espaco->{$tipo.'_id'}];
        if ($tipo === 'turma' && $v->perfil === 'professor') {
            return DB::table('vinculo_turmas as vt')->join('turmas as t', 't.id', '=', 'vt.turma_id')
                ->where('vt.vinculo_id', $v->id)->where('vt.espaco_id', $v->espaco_id)
                ->whereColumn('t.espaco_id', 'vt.espaco_id')->where('t.escola_id', $v->espaco->escola_id)
                ->orderBy('t.id')->pluck('t.id')->map(fn ($id) => (int) $id)->all();
        }
        if ($tipo === 'aluno' && $v->perfil === 'aluno') {
            return DB::table('vinculo_alunos as va')->join('alunos as a', 'a.id', '=', 'va.aluno_id')
                ->join('turmas as t', 't.id', '=', 'a.turma_id')->where('va.vinculo_id', $v->id)
                ->where('va.espaco_id', $v->espaco_id)->whereColumn('a.espaco_id', 'va.espaco_id')
                ->whereColumn('t.espaco_id', 'a.espaco_id')->where('t.escola_id', $v->espaco->escola_id)
                ->orderBy('a.id')->pluck('a.id')->map(fn ($id) => (int) $id)->all();
        }
        return [];
    }

    public static function escolas(Usuario $u): array
    {
        $e = self::resolver($u)->espaco;
        if ($e->tipo === 'pessoal') return [];
        $q = Espacos::escolas();
        match ($e->tipo) {
            'rede' => $q->where('n.rede_id', $e->rede_id),
            'nucleo' => $q->where('n.id', $e->nucleo_id),
            'escola' => $q->where('e.id', $e->escola_id),
        };
        return $q->orderBy('e.id')->pluck('e.id')->map(fn ($id) => (int) $id)->all();
    }

    public static function permissoes(Usuario $u): array
    {
        $v = self::resolver($u);
        $padrao = DB::table('perfil_permissoes')->where('perfil', $v->perfil)->where('tipo', 'padrao')->pluck('permissao')->all();
        $ajustes = DB::table('vinculo_permissoes')->where('vinculo_id', $v->id)
            ->where(fn ($q) => $q->whereNull('valida_ate')->orWhere('valida_ate', '>=', now()->toDateString()))->get();
        // Uma concessão inválida no catálogo não cria poder; retirada é sempre respeitada.
        $extras = DB::table('perfil_permissoes')->where('perfil', $v->perfil)->where('tipo', 'extra')->pluck('permissao')->all();
        $resultado = array_values(array_diff(array_unique(array_merge($padrao,
            array_intersect($extras, $ajustes->where('efeito', 'conceder')->pluck('permissao')->all()))),
            $ajustes->where('efeito', 'retirar')->pluck('permissao')->all()));
        sort($resultado);
        return $resultado;
    }

    /** Identifica vínculo, versão e alcance para futuros recibos/links contextualizados. */
    public static function versaoAcesso(Usuario $u): string
    {
        $v = self::resolver($u);
        return hash('sha256', json_encode([$u->id, $v->id, $v->espaco_id, $v->perfil,
            $v->versao_autorizacao, $v->inicio_em, $v->fim_em, self::escolas($u),
            self::referencias($u, 'turma'), self::referencias($u, 'aluno'), self::permissoes($u)], JSON_THROW_ON_ERROR));
    }
}
