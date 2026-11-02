<?php
namespace App\Services;
final class PacoteResultadosInterface
{
    public static function apresentar(array $p): array
    {
        $alunos=array_column($p['alunos'],null,'origem_id'); $turmas=array_column($p['turmas_aplicacao'],null,'origem_id');
        $materiais=array_column($p['materiais_observados'],null,'origem_id'); $resultados=[];
        foreach($p['resultados'] as $r) {
            $a=$alunos[$r['aluno_origem_id']]; $t=$turmas[$r['turma_origem_id']]; $m=$materiais[$r['prova_origem_id']]['observado'];
            $resultados[]=['aluno'=>$a['nome'],'matricula'=>$a['matricula'],'turma'=>$t['nome'],'prova'=>$m['prova']['titulo'],
                'dados'=>$r['dados'],'respostas'=>$r['respostas'],'versao_historica_comprovada'=>false];
        }
        return ['tipo_destino'=>$p['tipo_destino'],'condicao_historica'=>'legado_sem_versao_original','resultados'=>$resultados,
            'materiais'=>array_map(fn($m)=>['material'=>$m['observado']],$p['materiais_observados']),
            'alunos'=>array_map(fn($a)=>['nome'=>$a['nome'],'matricula'=>$a['matricula']],$p['alunos']),
            'turmas'=>array_map(fn($t)=>['nome'=>$t['nome'],'serie'=>$t['serie'],'turno'=>$t['turno'],'ano_letivo'=>$t['ano_letivo']],$p['turmas_aplicacao'])];
    }
}
