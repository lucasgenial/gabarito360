<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Usuario;
use App\Support\{ContextoInterno,ContextoVinculo,TokenInterno};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ExcecaoFuncionalController extends Controller {
 public static function autorizar(Request $r):void {abort_unless(TokenInterno::habilitado(),403);$a=ContextoInterno::resolver($r->user());abort_unless(in_array($a->perfil,['suporte','desenvolvedor'],true),403);}
 private function espaco(int $id):object {$e=DB::table('espacos')->where('id',$id)->where('tipo','escola')->first();abort_unless($e&&ContextoVinculo::espacoValido($e),404);return $e;}
 public function opcoes(Request $r){self::autorizar($r);$es=DB::table('espacos')->where('tipo','escola')->where('situacao','ativo')->orderBy('nome')->get()->filter(fn($e)=>ContextoVinculo::espacoValido($e))->map(fn($e)=>['id'=>$e->id,'nome'=>$e->nome]);return ApiResponse::success($es->values());}
 public function consultar(Request $r){self::autorizar($r);$d=$r->validate(['email'=>'required|email','espaco_id'=>'required|integer']);$this->espaco($d['espaco_id']);$u=Usuario::porEmail($d['email'])->where('ativo',true)->firstOrFail();abort_unless(DB::table('usuario_vinculos')->where('usuario_id',$u->id)->where('espaco_id',$d['espaco_id'])->exists(),404);return $this->dados($u,$d['espaco_id']);}
 private function dados(Usuario $u,int $espaco){$x=DB::table('usuario_funcoes_excecoes')->where('usuario_id',$u->id)->where('espaco_id',$espaco)->first();return ApiResponse::success(['usuario'=>['id'=>$u->id,'nome'=>$u->nome],'espaco_id'=>$espaco,'permitir'=>(bool)($x->permitir??false),'versao'=>$x->versao??0,'chamado'=>$x->chamado??null,'motivo'=>$x->motivo??null,'vinculos'=>DB::table('usuario_vinculos')->where('usuario_id',$u->id)->where('espaco_id',$espaco)->orderBy('id')->get(['perfil','situacao']), 'historico'=>DB::table('usuario_funcoes_eventos')->where('usuario_id',$u->id)->where('espaco_id',$espaco)->orderByDesc('id')->limit(20)->get(['permitir','chamado','motivo','ator_id','perfil_ator','ocorrido_em'])]);}
 public function salvar(Request $r){self::autorizar($r);$d=$r->validate(['usuario_id'=>'required|integer','espaco_id'=>'required|integer','permitir'=>'required|boolean','versao'=>'required|integer|min:0','chamado'=>'required|string|min:3|max:80','motivo'=>'required|string|min:10|max:500']);$this->espaco($d['espaco_id']);abort_if((int)$d['usuario_id']===(int)$r->user()->id,403,'Outro responsável interno deve autorizar sua própria exceção.');
  DB::transaction(function()use($d,$r){$u=Usuario::whereKey($d['usuario_id'])->where('ativo',true)->lockForUpdate()->firstOrFail();abort_unless(DB::table('usuario_vinculos')->where('usuario_id',$u->id)->where('espaco_id',$d['espaco_id'])->exists(),404);$x=DB::table('usuario_funcoes_excecoes')->where('usuario_id',$u->id)->where('espaco_id',$d['espaco_id'])->lockForUpdate()->first();abort_unless((int)($x->versao??0)===(int)$d['versao'],409,'Esta configuração mudou. Consulte novamente antes de salvar.');$a=ContextoInterno::resolver($r->user());$v=(int)$d['versao']+1;
   DB::table('usuario_funcoes_excecoes')->updateOrInsert(['usuario_id'=>$u->id,'espaco_id'=>$d['espaco_id']],['permitir'=>$d['permitir'],'versao'=>$v,'chamado'=>$d['chamado'],'motivo'=>$d['motivo'],'definida_por'=>$r->user()->id,'created_at'=>$x->created_at??now(),'updated_at'=>now()]);
   DB::table('usuario_funcoes_eventos')->insert(['usuario_id'=>$u->id,'espaco_id'=>$d['espaco_id'],'ator_id'=>$r->user()->id,'acesso_interno_id'=>$a->id,'perfil_ator'=>$a->perfil,'permitir'=>$d['permitir'],'versao'=>$v,'chamado'=>$d['chamado'],'motivo'=>$d['motivo'],'ocorrido_em'=>now()]);
  });return $this->dados(Usuario::findOrFail($d['usuario_id']),$d['espaco_id']);
 }
}
