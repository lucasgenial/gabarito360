<?php

namespace App\Services;

use App\Models\Usuario;
use App\Support\{ElegibilidadeCopia,Espacos,RegimeVinculos};
use Illuminate\Support\Facades\{DB,Validator};

/** Consulta somente da origem. Não autoriza transferência nem consulta o destinatário. */
final class PreviaCadastroCopia
{
    public const POLITICA='cadastro-minimo-previa-1';

    public function previaOrigem(Usuario $ator, int $origem, string $tipoDestino, array $turmas, array $alunos): array
    {
        abort_unless(Espacos::ativo() && RegimeVinculos::obrigatorio(),403,'Prévia indisponível.');
        $contexto=ElegibilidadeCopia::lado($ator,$origem,'origem');
        abort_unless(in_array($tipoDestino,['pessoal','escola'],true),422,'Tipo de destino inválido.');
        $total=count($turmas)+count($alunos);
        abort_unless($total>=1 && $total<=100,422,'Selecione entre 1 e 100 entidades.');
        foreach ([$turmas,$alunos] as $ids) {
            foreach ($ids as $id) abort_unless(is_int($id) && $id>0,422,'Seleção inválida.');
            abort_unless(array_is_list($ids) && count(array_unique($ids,SORT_REGULAR))===count($ids),422,'Seleção inválida.');
        }
        sort($turmas); sort($alunos);
        $selecionadas=Espacos::conferirEscola(DB::table('turmas'),'turmas')->where('espaco_id',$origem)
            ->whereIn('id',$turmas)->orderBy('id')->get(['id','nome','serie','turno','ano_letivo','ativo']);
        abort_unless($selecionadas->count()===count($turmas),404,'Seleção indisponível.');
        $saidaTurmas=[];
        foreach ($selecionadas as $t) {
            $dados=['nome'=>$t->nome,'serie'=>$t->serie,'turno'=>$t->turno,'ano_letivo'=>(int)$t->ano_letivo,'ativo'=>(bool)$t->ativo];
            $this->validar($dados,['nome'=>'required|string|max:50','serie'=>'required|string|max:50',
                'turno'=>'required|in:manha,tarde,noite,integral','ano_letivo'=>'required|integer|between:2020,2099','ativo'=>'boolean']);
            $saidaTurmas[]=['origem_id'=>(int)$t->id,'dados'=>$dados];
        }
        $campos=['id','turma_id','nome','matricula','avatar_id','ativo'];
        if ($tipoDestino==='escola') $campos[]='data_nascimento';
        $selecionados=DB::table('alunos')->where('espaco_id',$origem)->whereIn('id',$alunos)->orderBy('id')->get($campos);
        abort_unless($selecionados->count()===count($alunos),404,'Seleção indisponível.');
        $saidaAlunos=[]; $pendencias=[];
        foreach ($selecionados as $a) {
            abort_unless(in_array((int)$a->turma_id,$turmas,true),422,'Selecione explicitamente as turmas dos alunos.');
            $dados=['nome'=>$a->nome,'matricula'=>$a->matricula,'avatar_id'=>$a->avatar_id===null?null:(int)$a->avatar_id,'ativo'=>(bool)$a->ativo];
            $this->validar($dados,['nome'=>'required|string|max:200','matricula'=>'required|string|max:20',
                'avatar_id'=>'nullable|integer|between:1,36','ativo'=>'boolean']);
            if ($tipoDestino==='escola') {
                if ($a->data_nascimento===null || $a->data_nascimento==='') {
                    $pendencias[]=['entidade'=>'aluno','origem_id'=>(int)$a->id,'codigo'=>'campo_obrigatorio_ausente','campo'=>'data_nascimento'];
                } else {
                    $this->validar(['data_nascimento'=>$a->data_nascimento],['data_nascimento'=>'required|date_format:Y-m-d']);
                    $dados['data_nascimento']=$a->data_nascimento;
                }
            }
            $saidaAlunos[]=['origem_id'=>(int)$a->id,'turma_origem_id'=>(int)$a->turma_id,'dados'=>$dados];
        }
        $resultado=['politica'=>self::POLITICA,'origem_espaco_id'=>$origem,'tipo_destino'=>$tipoDestino,
            'contexto_origem'=>$contexto,'turmas'=>$saidaTurmas,'alunos'=>$saidaAlunos,'pendencias'=>$pendencias,
            'revisao_obrigatoria'=>true,'evidencia_identificados_obrigatoria'=>$saidaAlunos!==[],
            'autoriza_execucao'=>false];
        $resultado['conteudo_digest']=hash('sha256',json_encode($resultado,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return $resultado;
    }

    private function validar(array $dados, array $regras): void
    {
        abort_if(Validator::make($dados,$regras)->fails(),422,'Cadastro selecionado incompleto ou inválido.');
    }
}
