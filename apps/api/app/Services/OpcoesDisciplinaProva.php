<?php

namespace App\Services;

use App\Models\Usuario;
use App\Support\{Escopo, Permissao};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** DC1: sugestões internas. O resultado de GET nunca substitui autorização no commit/publicação. */
final class OpcoesDisciplinaProva
{
    public function listar(Usuario $ator, array $turmas, string $operacao = 'criar'): array
    {
        abort_unless(in_array($operacao, ['criar', 'editar', 'publicar_gabarito'], true), 422, 'Operação inválida.');
        $v = app(GestaoDisciplinas::class)->contexto($ator);
        $capacidade=$operacao==='publicar_gabarito' ? 'gabarito.editar' : 'prova.'.$operacao;
        abort_unless(Permissao::pode($ator, $capacidade), 403, 'Sem permissão para esta operação com provas.');
        if (!$turmas) return [];
        if (count($turmas) > 500 || count(array_unique($turmas, SORT_REGULAR)) !== count($turmas)
            || collect($turmas)->contains(fn ($id) => !is_int($id) || $id < 1)) {
            throw ValidationException::withMessages(['turma_ids' => 'Selecione até 500 turmas diferentes e válidas.']);
        }
        $alvo = Escopo::turmas($ator)->where('turmas.espaco_id', $v->espaco_id)->where('turmas.escola_id', $v->espaco->escola_id)
            ->where('turmas.ativo', true)->whereIn('turmas.id', $turmas);
        abort_unless($alvo->count() === count($turmas), 404, 'Uma das turmas não está disponível neste vínculo.');
        $q = DB::table('turma_disciplinas as td')->join('disciplinas as d', function ($j) {
            $j->on('d.id', '=', 'td.disciplina_id')->on('d.espaco_id', '=', 'td.espaco_id');
        })->where('td.espaco_id', $v->espaco_id)->whereIn('td.turma_id', $turmas)->where('td.ativo', true)->where('d.ativo', true);
        if ($v->perfil === 'professor') {
            $hoje = now('America/Sao_Paulo')->toDateString();
            $q->whereExists(fn ($a) => $a->selectRaw('1')->from('docente_atribuicoes as a')
                ->whereColumn('a.espaco_id', 'td.espaco_id')->whereColumn('a.turma_disciplina_id', 'td.id')
                ->where('a.vinculo_id', $v->id)->where('a.situacao', 'ativa')->where('a.inicio_em', '<=', $hoje)
                ->where(fn ($f) => $f->whereNull('a.fim_em')->orWhere('a.fim_em', '>', $hoje)))
                ->whereExists(fn ($p) => $p->selectRaw('1')->from('disciplina_professores as dp')
                    ->whereColumn('dp.disciplina_id', 'd.id')->whereColumn('dp.espaco_id', 'd.espaco_id')
                    ->where('dp.vinculo_id', $v->id)->where('dp.ativo', true));
        }
        // Interseção, nunca união: cada disciplina precisa existir em TODAS as turmas selecionadas.
        return $q->select('d.id', 'd.nome', 'd.sigla')->groupBy('d.id', 'd.nome', 'd.sigla')
            ->havingRaw('COUNT(DISTINCT td.turma_id) = ?', [count($turmas)])->orderBy('d.nome')->orderBy('d.id')->get()->all();
    }
}
