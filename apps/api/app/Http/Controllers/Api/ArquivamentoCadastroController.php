<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\ArquivamentoCadastro;
use Illuminate\Http\Request;
final class ArquivamentoCadastroController extends Controller {
 private function executar(Request $r,int $id,string $tipo){
  abort_if(array_diff(array_keys($r->all()),['arquivado','esperado_ativo','versao','chave','motivo']),422);
  $d=$r->validate(['arquivado'=>'required|boolean','esperado_ativo'=>'required|boolean','versao'=>'required|integer|min:0','chave'=>'required|uuid','motivo'=>'sometimes|nullable|string|max:500']);
  return ApiResponse::success(app(ArquivamentoCadastro::class)->executar($r->user(),$tipo,$id,$d),$d['arquivado']?'Cadastro arquivado. Histórico preservado.':'Cadastro desarquivado.');
 }
 public function turma(Request $r,int $id){return $this->executar($r,$id,'turma');}
 public function aluno(Request $r,int $id){return $this->executar($r,$id,'aluno');}
}
