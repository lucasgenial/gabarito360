<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class ExcecaoFuncionalWebTest extends TestCase {
 private function contexto(string $perfil='suporte'):string {
  config(['services.api.base_url'=>'http://api.test/api']);Http::preventStrayRequests();
  $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_internos'=>true,'auth_nome'=>'Suporte fictício']);$this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
  $d=['token'=>'interno','tipo_contexto'=>'interno','acesso_interno_id'=>1,'perfil'=>$perfil,'permissoes'=>$perfil==='suporte'?['usuario.excecao_funcional.gerir']:[]];
  Http::fake(function($r)use($d){$p=parse_url($r->url(),PHP_URL_PATH);if(str_ends_with($p,'/auth/me'))return Http::response(['data'=>$d]);if(preg_match('~/auth/(vinculos|acessos-internos)$~',$p))return Http::response(['data'=>[]]);if(str_ends_with($p,'/funcoes/opcoes'))return Http::response(['data'=>[['id'=>1,'nome'=>'Escola fictícia']]]);if(str_ends_with($p,'/funcoes/consultar'))return Http::response(['data'=>['usuario'=>['id'=>2,'nome'=>'Pessoa de teste'],'espaco_id'=>1,'permitir'=>false,'versao'=>0,'chamado'=>null,'motivo'=>null,'historico'=>[]]]);return Http::response([],403);});ContextoWeb::criar($d);return ContextoWeb::referencia($d);
 }
 public function test_suporte_consulta_e_formulario_exige_chamado_e_motivo():void {
  $id=$this->contexto();$this->get('/acessos/'.$id.'/configuracoes/usuarios')->assertOk()->assertSee('Configurações de usuários');
  $this->post('/acessos/'.$id.'/configuracoes/usuarios/consultar',['email'=>'pessoa@example.test','espaco_id'=>1])->assertOk()->assertSee('Pessoa de teste')->assertSee('Referência do chamado')->assertSee('Motivo da autorização')->assertSee('Registrar configuração');
  $this->post('/acessos/'.$id.'/configuracoes/usuarios',['usuario_id'=>2,'espaco_id'=>1,'permitir'=>1,'versao'=>0])->assertSessionHasErrors(['chamado','motivo']);
 }
 public function test_financeiro_nao_abre_configuracao():void {$id=$this->contexto('financeiro');$this->get('/acessos/'.$id.'/configuracoes/usuarios')->assertForbidden();}
}
