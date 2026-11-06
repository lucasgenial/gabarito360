<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{Escopo,Espacos,Permissao,EspacoPessoal};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Não move notas: congela o contexto de aplicação e registra a mudança de enturmação. */
final class MovimentarAlunoTurma
{
    public static function autorizado(Usuario $u): bool
    {
        return in_array($u->perfil,['coordenador','dir_escolar','vice_dir_escolar','secretario_escolar'],true) && Permissao::pode($u,'aluno.gerir');
    }

    public function executar(Usuario $u,int $id,array $dados,?array $pedido=null): void
    {
        DB::transaction(function()use($u,$id,$dados,$pedido){
            if(Espacos::ativo()) DB::table('espacos')->whereIn('id',\App\Support\Espacos::ids($u))->orderBy('id')->lockForUpdate()->get();
            $aluno=Escopo::alunos($u)->lockForUpdate()->findOrFail($id);
            $destinoId=(int)($dados['turma_id']??$aluno->turma_id);
            if($pedido){
                $hash=hash('sha256',json_encode([$id,$pedido['turma_origem_id'],$destinoId,$pedido['motivo']],JSON_THROW_ON_ERROR));
                $antes=DB::table('aluno_movimentacoes')->where('chave',$pedido['chave'])->first();
                if($antes){ abort_unless((int)$antes->ator_id===(int)$u->id && (int)$antes->ator_vinculo_id===(int)$u->vinculoContextoId() && hash_equals($antes->pedido_hash,$hash),409); return; }
                abort_unless((int)$pedido['turma_origem_id']===(int)$aluno->turma_id,409,'A turma do aluno mudou. Atualize os dados antes de confirmar.');
                abort_unless($aluno->ativo,422,'Ative o aluno antes de transferir.');
                abort_unless($destinoId!==(int)$aluno->turma_id,422,'Escolha outra turma.');
            }
            if($destinoId!==(int)$aluno->turma_id){
                abort_unless(EspacoPessoal::doAtor($u)||self::autorizado($u),403);
                $origem=Escopo::turmas($u)->findOrFail($aluno->turma_id);
                $destino=Escopo::turmas($u)->where('ativo',true)->findOrFail($destinoId);
                abort_unless((int)$origem->escola_id===(int)$destino->escola_id && (!Espacos::ativo()||(int)$destino->espaco_id===(int)$aluno->espaco_id),422,'A transferência deve ocorrer entre turmas da mesma escola.');
                // Cartões em processamento podem ainda descobrir o aluno pelo QR. Nenhuma
                // alteração é executada até a fila e as correções desta escola terminarem.
                abort_if(DB::table('cartoes')->where('espaco_id',$aluno->espaco_id)->whereIn('status',['pendente','processando'])->exists()
                    || DB::table('cartao_envios')->where('espaco_id',$aluno->espaco_id)->whereIn('status',['na_fila','processando'])->exists(),409,'Há leituras de cartões em andamento. Aguarde a conclusão antes de transferir.');
                $cartoes=DB::table('cartoes')->where('aluno_id',$id)->orderBy('id')->lockForUpdate()->get();
                foreach($cartoes as $c){
                    $nota=DB::table('notas')->where('cartao_id',$c->id)->lockForUpdate()->first();
                    $turma=$c->turma_aplicacao_id??$nota?->turma_id??$aluno->turma_id;
                    abort_unless((int)$c->espaco_id===(int)$aluno->espaco_id && (!$nota||((int)$nota->espaco_id===(int)$aluno->espaco_id&&(int)$nota->aluno_id===$id&&(int)$nota->prova_id===(int)$c->prova_id&&(int)$nota->turma_id===(int)$turma)),409,'Histórico precisa de conciliação. Nenhum dado foi movido.');
                    abort_unless(DB::table('prova_turmas')->where('espaco_id',$aluno->espaco_id)->where('prova_id',$c->prova_id)->where('turma_id',$turma)->exists(),409,'Histórico precisa de conciliação.');
                    DB::table('cartoes')->where('id',$c->id)->update(['turma_aplicacao_id'=>$turma]);
                }
                if(Espacos::ativo()&&!EspacoPessoal::doAtor($u)) DB::table('aluno_movimentacoes')->insert([
                    'id'=>(string)Str::uuid(),'chave'=>$pedido['chave']??(string)Str::uuid(),'pedido_hash'=>$hash??hash('sha256',json_encode([$id,$aluno->turma_id,$destinoId])),
                    'aluno_id'=>$id,'espaco_id'=>$aluno->espaco_id,'escola_id'=>$origem->escola_id,'turma_origem_id'=>$aluno->turma_id,'turma_destino_id'=>$destinoId,
                    'ator_id'=>$u->id,'ator_vinculo_id'=>$u->vinculoContextoId(),'ator_perfil'=>$u->perfil,'motivo'=>\Illuminate\Support\Facades\Crypt::encryptString($pedido['motivo']??'Alteração de turma pelo cadastro'),'ocorrido_em'=>now(),
                ]);
            }
            $aluno->update($dados);
        },3);
    }
}
