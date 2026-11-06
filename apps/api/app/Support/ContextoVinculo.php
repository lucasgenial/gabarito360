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
        if ($e->tipo==='escola') GruposFuncionais::conferir((int)$u->id,(int)$v->espaco_id,$v->perfil,now(),$v->fim_em,(int)$v->id,403);
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
        $cliente = DB::table('clientes')->where('id', $e->cliente_id)->where('situacao', 'ativo');
        if ($e->tipo === 'pessoal') {
            return $cliente->whereExists(DB::table('usuarios')->selectRaw('1')
                ->where('id', $e->usuario_proprietario_id)->where('ativo', true))->exists();
        }
        // Uma statement atual por uso, sem guardar concessões ou IDs entre consultas.
        return $cliente->whereExists(self::instituicaoValida($e->tipo, $e->{$e->tipo.'_id'}, $e->cliente_id))->exists();
    }

    private static function instituicaoValida(string $tipo, mixed $referencia, mixed $cliente, bool $coluna = false, int $nivel = 0): \Illuminate\Database\Query\Builder
    {
        $tabela = match ($tipo) { 'rede' => 'redes', 'nucleo' => 'nucleos', 'escola' => 'escolas' };
        $i = 'instituicao_valida_'.$nivel;
        $q = DB::table($tabela.' as '.$i)->selectRaw('1');
        if ($coluna) $q->whereColumn($i.'.id', $referencia);
        else $q->where($i.'.id', $referencia);
        if ($tipo === 'rede') return $q;

        $tipoPai = $tipo === 'escola' ? 'nucleo' : 'rede';
        $p = 'espaco_pai_valido_'.$nivel;
        $pai = DB::table('espacos as '.$p)->selectRaw('1')
            ->where($p.'.tipo', $tipoPai)->where($p.'.situacao', 'ativo')->where($p.'.cliente_id', $cliente)
            ->whereColumn($p.'.'.$tipoPai.'_id', $i.'.'.$tipoPai.'_id');
        foreach (['rede_id', 'nucleo_id', 'escola_id', 'usuario_proprietario_id'] as $campo) {
            if ($campo === $tipoPai.'_id') $pai->whereNotNull($p.'.'.$campo);
            else $pai->whereNull($p.'.'.$campo);
        }
        return $q->whereExists($pai->whereExists(self::instituicaoValida($tipoPai, $p.'.'.$tipoPai.'_id', $cliente, true, $nivel + 1)));
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
