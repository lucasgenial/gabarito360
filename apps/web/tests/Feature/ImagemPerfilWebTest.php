<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class ImagemPerfilWebTest extends TestCase {
 private function contexto():string {
  config(['services.api.base_url'=>'http://api.test/api']);Http::preventStrayRequests();
  $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Pessoa de teste']);$this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
  $d=['token'=>'escolar','perfil'=>'auxiliar_secretaria','permissoes'=>['aluno.ver'],'id'=>1,'nome'=>'Pessoa de teste','cpf'=>'00000000001','email'=>'pessoa@example.test','escola_nome'=>'Escola fictícia','created_at'=>'2026-01-01','avatar_id'=>7,'tem_foto'=>false,'imagem_versao'=>1,'espaco'=>['id'=>1,'nome'=>'Escola fictícia','tipo'=>'escola']];
  Http::fake(function($r)use($d){if(str_ends_with($r->url(),'/auth/vinculos'))return Http::response(['data'=>[]]);if(str_ends_with($r->url(),'/auth/me'))return Http::response(['data'=>$d]);if(str_ends_with($r->url(),'/auth/imagem'))return Http::response(['data'=>['mensagem'=>'Atualizada','avatar_id'=>8,'tem_foto'=>false,'imagem_versao'=>2,'token'=>'nao-devolver']]);return Http::response([],404);});return ContextoWeb::criar($d);
 }
 public function test_proprio_perfil_oferece_foto_e_galeria_sem_permissoes_novas():void {
  $id=$this->contexto();$this->get('/perfil?_ctx='.$id)->assertOk()->assertSee('Sua imagem de perfil')->assertSee('Escolha um avatar')->assertSee('Salvar imagem')->assertSee('multipart/form-data',false);
  $this->post('/perfil/imagem',['_ctx'=>$id,'tipo'=>'avatar','avatar_id'=>8])->assertRedirect()->assertSessionHas('sucesso_imagem');
  Http::assertSent(fn($r)=>str_ends_with($r->url(),'/auth/imagem')&&$r['avatar_id']==8);
 }
 public function test_upload_e_encaminhado_como_arquivo_e_svg_rejeitado():void {
  $id=$this->contexto();$this->post('/perfil/imagem',['_ctx'=>$id,'tipo'=>'foto','foto'=>UploadedFile::fake()->image('perfil.jpg')])->assertRedirect()->assertSessionHas('sucesso_imagem');
  Http::assertSent(fn($r)=>str_ends_with($r->url(),'/auth/imagem')&&$r->method()==='POST');
  $this->post('/perfil/imagem',['_ctx'=>$id,'tipo'=>'foto','foto'=>UploadedFile::fake()->createWithContent('foto.svg','<svg/>')])->assertSessionHasErrors('foto',null,'imagem');
 }

 public function test_modal_salva_json_sem_redirecionar_ou_expor_token():void {
  $id=$this->contexto();$this->postJson('/perfil/imagem',['_ctx'=>$id,'tipo'=>'avatar','avatar_id'=>8])->assertOk()->assertJsonPath('data.avatar_id',8)->assertJsonPath('data.tem_foto',false)->assertJsonMissingPath('data.token')->assertDontSee('nao-devolver');
  $this->assertSame(8,session('auth_avatar_id'));
 }
}
