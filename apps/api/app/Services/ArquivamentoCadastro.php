<?php
namespace App\Services;

use App\Models\{Usuario,Turma,Aluno};
use App\Support\{Escopo,Espacos,Permissao};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB,Crypt};
use Illuminate\Support\Str;

/** Estado cadastral apenas: nenhuma matrícula, prova, vínculo ou dado histórico é encerrado. */
final class ArquivamentoCadastro
{
 use \Illuminate\Database\DetectsConcurrencyErrors;
 private function transacionar(\Closure $acao):mixed {
  $c=DB::connection();
  if(!in_array($c->getDriverName(),['mysql','mariadb'],true))return $c->transaction($acao,3);
  if($c->transactionLevel()!==0)throw new \LogicException('Arquivamento exige transação própria.');
  for($n=1;;$n++){
   $c->statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
   try{return $c->transaction($acao,1);}catch(\Throwable $e){if($n>=3||!$this->causedByConcurrencyError($e))throw $e;}
  }
 }
 private function travarAtor(Usuario $u):void {
  if($id=$u->vinculoContextoId())DB::table('usuario_vinculos')->where('id',$id)->lockForUpdate()->first();
  else DB::table('usuarios')->where('id',$u->id)->lockForUpdate()->first();
  if(!$id){$atual=Usuario::find($u->id);abort_unless($atual && $atual->getRawOriginal('perfil')===$u->getRawOriginal('perfil'),403);}
  Permissao::limparCache();
  \App\Support\RegimeVinculos::exigir($u);
  abort_unless(DB::table('usuarios')->where('id',$u->id)->where('ativo',true)->exists(),403);
  if(Espacos::ativo())DB::table('espacos')->whereIn('id',Espacos::ids($u))->orderBy('id')->lockForUpdate()->get();
  \App\Support\RegimeVinculos::exigir($u);
 }
 private function recurso(Usuario $u,string $tipo,int $id):Model {
  return match($tipo){'turma'=>Escopo::turmas($u)->lockForUpdate()->findOrFail($id),'aluno'=>Escopo::alunos($u)->lockForUpdate()->findOrFail($id),default=>throw new \InvalidArgumentException('Cadastro inválido.')};
 }
 private function tipo(Model $m):string {return match(true){$m instanceof Turma=>'turma',$m instanceof Aluno=>'aluno',default=>throw new \InvalidArgumentException('Cadastro inválido.')};}
 public function versoes(string $tipo,array $ids):array {
  abort_unless(in_array($tipo,['turma','aluno'],true),422);
  return DB::table('cadastro_arquivamentos')->where('entidade',$tipo)->whereIn('registro_id',$ids)->groupBy('registro_id')->select('registro_id')->selectRaw('MAX(versao) as versao')->pluck('versao','registro_id')->map(fn($v)=>(int)$v)->all();
 }
 public function estado(Model $m,?int $versao=null):array {
  $v=$versao??($this->versoes($this->tipo($m),[$m->id])[$m->id]??0);
  return ['arquivado'=>!(bool)$m->ativo,'versao'=>$v];
 }
 /** Apenas associações novas; vínculos históricos já existentes não são removidos. Exige transação do chamador. */
 public function exigirTurmasAtivas(Usuario $u,array $ids,?int $provaId=null):void {
  if(!$ids)return;
  if(Espacos::ativo())DB::table('espacos')->whereIn('id',Espacos::ids($u))->orderBy('id')->lockForUpdate()->get();
  if($provaId){
   abort_unless(Escopo::provaEditavel($u,$provaId),404);
   $existentes=DB::table('prova_turmas')->where('prova_id',$provaId)->when(Espacos::ativo(),fn($q)=>$q->whereIn('espaco_id',Espacos::ids($u)))->pluck('turma_id')->all();
   $ids=array_values(array_diff($ids,$existentes));
  }
  if(!$ids)return;
  $turmas=Escopo::turmas($u)->whereIn('turmas.id',$ids)->orderBy('turmas.id')->lockForUpdate()->get();
  abort_unless($turmas->count()===count(array_unique($ids)),404);
  abort_if($turmas->contains(fn($t)=>!$t->ativo),422,'Desarquive a turma antes de criar uma nova associação.');
 }
 private function gravar(Usuario $u,Model $m,bool $ativo,array $pedido=[],bool $estrito=false):array {
  $tipo=$this->tipo($m);abort_unless(Permissao::pode($u,$tipo.'.desativar'),403);
  $versao=$this->estado($m)['versao'];$chave=strtolower($pedido['chave']??(string)Str::uuid());
  $hash=hash('sha256',json_encode([$tipo,(int)$m->id,$m->espaco_id,$u->id,$u->vinculoContextoId(),$ativo,array_key_exists('esperado_ativo',$pedido)?(bool)$pedido['esperado_ativo']:null,array_key_exists('versao',$pedido)?(int)$pedido['versao']:null,$pedido['motivo']??null],JSON_THROW_ON_ERROR));
  $anterior=DB::table('cadastro_arquivamentos')->where('chave',$chave)->first();
  if($anterior){
   abort_unless((int)$anterior->usuario_id===(int)$u->id && ($anterior->vinculo_id===null ? $u->vinculoContextoId()===null : (int)$anterior->vinculo_id===$u->vinculoContextoId()) && $anterior->entidade===$tipo && (int)$anterior->registro_id===(int)$m->id && hash_equals($anterior->pedido_hash,$hash),409,'Identificador já utilizado em outra solicitação.');
   return array_replace(json_decode($anterior->resultado,true,512,JSON_THROW_ON_ERROR),['repetido'=>true]);
  }
  if($estrito||array_key_exists('esperado_ativo',$pedido))abort_unless((bool)$m->ativo===(bool)$pedido['esperado_ativo'],409,'O estado do cadastro mudou. Atualize antes de confirmar.');
  if($estrito||array_key_exists('versao',$pedido))abort_unless($versao===(int)$pedido['versao'],409,'O estado do cadastro mudou. Atualize antes de confirmar.');
  $antes=(bool)$m->ativo;$mudou=$antes!==$ativo;
  if($mudou){$m->update(['ativo'=>$ativo]);$versao++;}
  $resultado=['ativo'=>$ativo,'arquivado'=>!$ativo,'versao'=>$versao,'alterado'=>$mudou,'repetido'=>false];
  DB::table('cadastro_arquivamentos')->insert([
   'id'=>(string)Str::uuid(),'entidade'=>$tipo,'registro_id'=>$m->id,'espaco_id'=>$m->espaco_id,'usuario_id'=>$u->id,'vinculo_id'=>$u->vinculoContextoId(),'ator_perfil'=>$u->perfil,
   'chave'=>$chave,'pedido_hash'=>$hash,'ativo_antes'=>$antes,'ativo_depois'=>$ativo,'versao'=>$versao,'sequencia'=>$mudou?$versao:null,'alterado'=>$mudou,
   'motivo'=>isset($pedido['motivo'])?Crypt::encryptString($pedido['motivo']):null,'resultado'=>json_encode($resultado,JSON_THROW_ON_ERROR),'ocorrido_em'=>now()->utc(),
  ]);
  return $resultado;
 }
 public function executar(Usuario $u,string $tipo,int $id,array $pedido):array {
  return $this->transacionar(function()use($u,$tipo,$id,$pedido){$this->travarAtor($u);$m=$this->recurso($u,$tipo,$id);return $this->gravar($u,$m,!(bool)$pedido['arquivado'],$pedido,true);});
 }
 public function toggleLegado(Usuario $u,string $tipo,int $id,array $pedido=[]):array {
  return $this->transacionar(function()use($u,$tipo,$id,$pedido){$this->travarAtor($u);$m=$this->recurso($u,$tipo,$id);return $this->gravar($u,$m,array_key_exists('esperado_ativo',$pedido)?!(bool)$pedido['esperado_ativo']:!(bool)$m->ativo,$pedido);});
 }
 /** PUT existente: callback mantém regras cadastrais/transferência; estado passa só pela política. */
 public function atualizar(Usuario $u,string $tipo,int $id,array $dados,?\Closure $gravar=null):Model {
  return $this->transacionar(function()use($u,$tipo,$id,$dados,$gravar){
   $this->travarAtor($u);abort_unless(Permissao::pode($u,$tipo.'.gerir'),403);$m=$this->recurso($u,$tipo,$id);
   if(array_key_exists('ativo',$dados)&&((bool)$dados['ativo']!==(bool)$m->ativo))$this->gravar($u,$m,(bool)$dados['ativo']);
   unset($dados['ativo']);if($gravar)$gravar($m,$dados);elseif($dados)$m->update($dados);
   return $m->fresh();
  });
 }
 public function criar(Usuario $u,string $tipo,array $dados,\Closure $criar):Model {
  return $this->transacionar(function()use($u,$tipo,$dados,$criar){
   $this->travarAtor($u);abort_unless(Permissao::pode($u,$tipo.'.gerir'),403);
   $ativo=(bool)($dados['ativo']??true);if(!$ativo)abort_unless(Permissao::pode($u,$tipo.'.desativar'),403);
   unset($dados['ativo']);$m=$criar($dados)->fresh();
   if(!$ativo)$this->gravar($u,$m,false);
   return $m->fresh();
  });
 }
}
