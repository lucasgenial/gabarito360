<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
final class GruposFuncionais {
 public const ADMINISTRATIVOS=['secretario_escolar','auxiliar_secretaria'];
 public const PEDAGOGICOS=['professor','coordenador','dir_escolar','vice_dir_escolar','aplicador'];
 public static function conferir(int $usuario,int $espaco,string $perfil,$inicio=null,$fim=null,?int $ignorar=null,int $status=422):void {
  $opostos=in_array($perfil,self::ADMINISTRATIVOS,true)?self::PEDAGOGICOS:(in_array($perfil,self::PEDAGOGICOS,true)?self::ADMINISTRATIVOS:[]);
  if(!$opostos)return;
  $inicio=$inicio??now();
  $q=DB::table('usuario_vinculos')->where('usuario_id',$usuario)->where('espaco_id',$espaco)->whereIn('perfil',$opostos)
    ->whereIn('situacao',['convidado','ativo','suspenso'])->where(fn($q)=>$q->whereNull('fim_em')->orWhere('fim_em','>',$inicio));
  if($fim)$q->where('inicio_em','<',$fim);
  if($ignorar)$q->where('id','!=',$ignorar);
  if(\Illuminate\Support\Facades\Schema::hasTable('usuario_funcoes_excecoes'))$q->whereNotExists(
   DB::table('usuario_funcoes_excecoes')->selectRaw('1')->where('usuario_id',$usuario)->where('espaco_id',$espaco)->where('permitir',true));
  abort_if($q->exists(),$status,'Esta instituição já possui um vínculo de outro grupo funcional para esta pessoa. Encerre o vínculo anterior ou solicite ao suporte uma exceção vinculada a chamado.');
 }
}
