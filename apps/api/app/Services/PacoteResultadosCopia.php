<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\MaterialCopia;
use Illuminate\Support\Facades\DB;

/** Projeção histórica identificada; chamar dentro da transação do lote. */
final class PacoteResultadosCopia
{
    public const CATEGORIAS=['resultado_armazenado','respostas','material_observado','aluno_nome_matricula','turma_aplicacao'];

    public function capturar(Usuario $ator,int $origem,int $destino,string $tipoDestino,array $notas): array
    {
        if (DB::transactionLevel()===0) throw new \LogicException('Pacote exige transação.');
        $p=(new PreviaResultadosCopia)->previaOrigem($ator,$origem,$tipoDestino,$notas);
        $deps=[];
        foreach (['provas'=>'prova_origem_id','alunos'=>'aluno_origem_id','turmas'=>'turma_origem_id'] as $grupo=>$campo) {
            $deps[$grupo]=array_values(array_unique(array_column($p['resultados'],$campo))); sort($deps[$grupo]);
        }
        $materiais=[];
        foreach ($deps['provas'] as $id) $materiais[]=['origem_id'=>$id,'observado'=>MaterialCopia::projetar($origem,$id)];
        $alunos=[];
        foreach ($deps['alunos'] as $id) {
            $a=DB::table('alunos')->where('espaco_id',$origem)->where('id',$id)->first(['nome','matricula']);
            abort_unless($a && is_string($a->nome) && trim($a->nome)!=='' && is_string($a->matricula) && trim($a->matricula)!=='',422,'Identificação incompleta.');
            $alunos[]=['origem_id'=>$id,'nome'=>$a->nome,'matricula'=>$a->matricula];
        }
        $turmas=[];
        foreach ($deps['turmas'] as $id) {
            $t=DB::table('turmas')->where('espaco_id',$origem)->where('id',$id)->first(['nome','serie','turno','ano_letivo']);
            abort_unless($t,409,'Turma indisponível.');
            $turmas[]=['origem_id'=>$id,'nome'=>$t->nome,'serie'=>$t->serie,'turno'=>$t->turno,'ano_letivo'=>(int)$t->ano_letivo];
        }
        $resultados=$p['resultados'];
        foreach ($resultados as &$r) {
            $r['respostas']=DB::table('cartao_respostas')->where('espaco_id',$origem)->where('cartao_id',$r['cartao_origem_id'])
                ->orderBy('numero_questao')->get(['numero_questao','alternativa'])->map(fn($q)=>['numero_questao'=>(int)$q->numero_questao,'alternativa'=>$q->alternativa])->all();
        }
        unset($r);
        return ['politica'=>LotesResultadosCopia::POLITICA,'origem_espaco_id'=>$origem,'destino_espaco_id'=>$destino,
            'tipo_destino'=>$tipoDestino,'condicao_historica'=>'legado_sem_versao_original','dependencias'=>$deps,
            'resultados'=>$resultados,'materiais_observados'=>$materiais,'alunos'=>$alunos,'turmas_aplicacao'=>$turmas];
    }
}
