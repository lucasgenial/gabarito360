<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{Escopo, EspacoPessoal};
use Illuminate\Support\Facades\DB;

/** Fixação histórica e mudança de turma são uma única transação. */
final class AtualizarAlunoPessoal
{
    public function executar(Usuario $ator, int $id, array $dados): void
    {
        abort_unless(EspacoPessoal::doAtor($ator), 403);
        DB::transaction(function () use ($ator, $id, $dados) {
            $aluno = Escopo::alunos($ator)->lockForUpdate()->findOrFail($id);
            if (isset($dados['turma_id']) && (int) $dados['turma_id'] !== (int) $aluno->turma_id) {
                $destino = Escopo::turmas($ator)->where('espaco_id', $aluno->espaco_id)->where('ativo', true)->lockForUpdate()->findOrFail($dados['turma_id']);
                $cartoes = DB::table('cartoes')->where('aluno_id', $id)->orderBy('id')->lockForUpdate()->get();
                foreach ($cartoes as $cartao) {
                    abort_unless((int) $cartao->espaco_id === (int) $aluno->espaco_id, 409, 'Histórico precisa de conciliação.');
                    $nota = DB::table('notas')->where('cartao_id', $cartao->id)->lockForUpdate()->first();
                    $turma = $cartao->turma_aplicacao_id ?? $nota?->turma_id ?? $aluno->turma_id;
                    abort_unless(!$nota || ((int) $nota->espaco_id === (int) $aluno->espaco_id && (int) $nota->aluno_id === $id
                        && (int) $nota->prova_id === (int) $cartao->prova_id && (int) $nota->turma_id === (int) $turma), 409, 'Histórico precisa de conciliação.');
                    abort_unless(DB::table('prova_turmas')->where('espaco_id', $aluno->espaco_id)->where('prova_id', $cartao->prova_id)->where('turma_id', $turma)->exists(), 409, 'Histórico precisa de conciliação.');
                    DB::table('cartoes')->where('id', $cartao->id)->update(['turma_aplicacao_id'=>$turma]);
                }
                $dados['turma_id'] = $destino->id;
            }
            $aluno->update(EspacoPessoal::dadosMinimos($dados));
        }, 3);
    }
}
