<?php

namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoCorrecao,ElegibilidadeCopia,Espacos,MaterialCopia,RegimeVinculos};
use Illuminate\Support\Facades\DB;

/** Prévia observacional da origem; não é versão histórica nem autorização. */
final class PreviaResultadosCopia
{
    public const POLITICA = 'resultados-previa-1';

    public function previaOrigem(Usuario $ator, int $origem, string $tipoDestino, array $notas): array
    {
        abort_unless(Espacos::ativo() && RegimeVinculos::obrigatorio(),403,'Prévia indisponível.');
        $contexto=ElegibilidadeCopia::lado($ator,$origem,'origem');
        abort_unless(in_array($tipoDestino,['pessoal','escola'],true),422,'Tipo de destino inválido.');
        abort_unless(array_is_list($notas) && count($notas)>=1 && count($notas)<=20
            && count(array_unique($notas,SORT_REGULAR))===count($notas),422,'Seleção inválida.');
        foreach ($notas as $id) abort_unless(is_int($id) && $id>0,422,'Seleção inválida.');
        sort($notas);
        $linhas=DB::table('notas')->where('espaco_id',$origem)->whereIn('id',$notas)->orderBy('id')->get();
        abort_unless($linhas->count()===count($notas),404,'Seleção indisponível.');
        $resultados=[];
        foreach ($linhas as $n) {
            $c=DB::table('cartoes')->where('espaco_id',$origem)->where('id',$n->cartao_id)->first();
            $a=DB::table('alunos')->where('espaco_id',$origem)->where('id',$n->aluno_id)->first(['id','turma_id']);
            $t=Espacos::conferirEscola(DB::table('turmas'),'turmas')->where('espaco_id',$origem)->where('id',$n->turma_id)->first(['id']);
            abort_unless($c && $a && $t && (int)$c->prova_id===(int)$n->prova_id
                && (int)$c->aluno_id===(int)$n->aluno_id
                && (int)($c->turma_aplicacao_id ?? $a->turma_id)===(int)$n->turma_id
                && DB::table('prova_turmas')->where('espaco_id',$origem)->where('prova_id',$n->prova_id)->where('turma_id',$n->turma_id)->exists(),409,'Dependências do resultado divergentes.');
            abort_unless($c->status==='lido' && in_array($c->lingua,[null,'ING','ESP'],true),422,'Cartão não finalizado.');
            ContextoCorrecao::exigirFilhos('gabaritos','prova_id',(int)$n->prova_id,$origem);
            $g=DB::table('gabaritos')->where('prova_id',$n->prova_id)->first();
            abort_unless($g,422,'Gabarito indisponível.');
            ContextoCorrecao::exigirFilhos('gabarito_questoes','gabarito_id',(int)$g->id,$origem);
            $material=MaterialCopia::projetar($origem,(int)$n->prova_id);
            ContextoCorrecao::exigirFilhos('cartao_respostas','cartao_id',(int)$c->id,$origem);
            $respostas=DB::table('cartao_respostas')->where('cartao_id',$c->id)->orderBy('numero_questao')->get(['numero_questao','alternativa','ambigua']);
            $qtd=$material['prova']['num_questoes'];
            abort_unless($respostas->count()===$qtd,422,'Respostas incompletas.');
            $permitidas=array_slice(['A','B','C','D','E'],0,$material['prova']['num_alternativas']);
            $normalizadas=[];
            foreach ($respostas as $i=>$r) {
                abort_unless((int)$r->numero_questao===$i+1 && (int)$r->ambigua===0
                    && ($r->alternativa===null || in_array($r->alternativa,$permitidas,true)),422,'Resposta não finalizada.');
                $normalizadas[]=['numero'=>$i+1,'alternativa'=>$r->alternativa];
            }
            // Limites estruturais, sem recalcular ou atribuir a nota ao gabarito atual.
            abort_unless((int)$n->acertos>=0 && (int)$n->total_questoes>=0 && (int)$n->total_questoes<=100
                && (int)$n->acertos<=(int)$n->total_questoes && is_numeric($n->nota_final)
                && (float)$n->nota_final>=0 && (float)$n->nota_final<=999.99
                && in_array($n->status_aprovacao,['aprovado','recuperacao'],true),422,'Resultado inválido.');
            $resultados[]=['nota_origem_id'=>(int)$n->id,'cartao_origem_id'=>(int)$c->id,
                'prova_origem_id'=>(int)$n->prova_id,'aluno_origem_id'=>(int)$a->id,'turma_origem_id'=>(int)$t->id,
                'dados'=>['acertos'=>(int)$n->acertos,'total_questoes'=>(int)$n->total_questoes,
                    'nota_final'=>number_format((float)$n->nota_final,2,'.',''),'status_aprovacao'=>$n->status_aprovacao,'lingua'=>$c->lingua],
                'contexto_observado_digest'=>$this->digest([$material,$normalizadas]),
                'versao_historica_comprovada'=>false];
        }
        $previa=['politica'=>self::POLITICA,'origem_espaco_id'=>$origem,'tipo_destino'=>$tipoDestino,
            'contexto_origem'=>$contexto,'resultados'=>$resultados,'autoriza_execucao'=>false,
            'evidencia_resultados_obrigatoria'=>true,'bloqueios'=>['preservacao_historica_pendente','evidencia_resultados_pendente','execucao_resultados_pendente']];
        $previa['conteudo_digest']=$this->digest($previa);
        return $previa;
    }

    private function digest(array $dados): string
    {
        return hash('sha256',json_encode($dados,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    }
}
