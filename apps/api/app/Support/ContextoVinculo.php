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
        // Uma statement atual, sem estado de autorização no ator ou entre chamadas.
        $q = PredicadosVinculo::contexto($u, false)->select('cv.*')
            ->selectSub(PredicadosVinculo::conflito()->limit(1), 'contexto_conflito');
        $campos = ['id', 'cliente_id', 'tipo', 'nome', 'situacao', 'rede_id', 'nucleo_id', 'escola_id',
            'usuario_proprietario_id', 'criado_por', 'created_at', 'updated_at'];
        foreach ($campos as $campo) $q->addSelect('ce.'.$campo.' as contexto_espaco_'.$campo);
        $v = $q->first();
        abort_unless($v, 403, 'Vínculo indisponível.');
        abort_if($v->contexto_conflito !== null, 403,
            'Esta instituição já possui um vínculo de outro grupo funcional para esta pessoa. Encerre o vínculo anterior ou solicite ao suporte uma exceção vinculada a chamado.');
        unset($v->contexto_conflito);
        $e = new \stdClass();
        foreach ($campos as $campo) {
            $alias = 'contexto_espaco_'.$campo;
            $e->$campo = $v->$alias;
            unset($v->$alias);
        }
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
        return PredicadosVinculo::instituicao($tipo, $referencia, $cliente, $coluna, $nivel);
    }

    public static function referencias(Usuario $u, string $tipo): array
    {
        return ConsultaVinculo::para($u)->referencias($tipo)->orderBy(DB::raw('1'))->get()
            ->map(fn ($linha) => (int) array_values((array) $linha)[0])->all();
    }

    private static function referenciasDoVinculo(object $v, string $tipo): array
    {
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
        return ConsultaVinculo::para($u)->escolas()->orderBy('e.id')->pluck('e.id')->map(fn ($id) => (int) $id)->all();
    }

    private static function escolasDoVinculo(object $v): array
    {
        $e = $v->espaco;
        if ($e->tipo === 'pessoal') return [];
        $q = Espacos::escolas();
        match ($e->tipo) {
            'rede' => $q->where('n.rede_id', $e->rede_id),
            'nucleo' => $q->where('n.id', $e->nucleo_id),
            'escola' => $q->where('e.id', $e->escola_id),
        };
        return $q->orderBy('e.id')->pluck('e.id')->map(fn ($id) => (int) $id)->all();
    }

    /** IDs materializados para esta operação; cada chamada revalida o contexto inteiro. */
    public static function espacos(Usuario $u): array
    {
        abort_if(ContextoInterno::presente($u), 403, 'Selecione um vínculo pedagógico válido.');
        return ConsultaVinculo::para($u)->espacos()->get()->map(fn ($linha) => (int) array_values((array) $linha)[0])->all();
    }

    public static function permissoes(Usuario $u): array
    {
        return self::permissoesDoVinculo(self::resolver($u));
    }

    private static function permissoesDoVinculo(object $v): array
    {
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
        // Reutiliza somente a validação desta chamada. Nunca guarda concessões no ator ou entre usos.
        return hash('sha256', json_encode([$u->id, $v->id, $v->espaco_id, $v->perfil,
            $v->versao_autorizacao, $v->inicio_em, $v->fim_em, self::escolasDoVinculo($v),
            self::referenciasDoVinculo($v, 'turma'), self::referenciasDoVinculo($v, 'aluno'), self::permissoesDoVinculo($v)], JSON_THROW_ON_ERROR));
    }
}
