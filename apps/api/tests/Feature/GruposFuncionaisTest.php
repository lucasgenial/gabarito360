<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Support\{GruposFuncionais,ContextoInterno};
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class GruposFuncionaisTest extends SecretarioEscolarTest {
 private function semExcecao():void {DB::table('usuario_funcoes_excecoes')->where('usuario_id',2)->update(['permitir'=>false]);}
 private function interno(string $perfil='suporte'):void {
  config(['vinculos.obrigatorio'=>true,'acessos_internos.ativo'=>true]);
  DB::table('usuario_acessos_internos')->insert(['id'=>20,'usuario_id'=>1,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
  app('auth')->forgetGuards();Sanctum::actingAs(Usuario::find(1)->noAcessoInterno(20,1));
 }
 public function test_sem_excecao_nao_acumula_grupos_na_mesma_escola():void {
  $this->semExcecao();Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
  $this->postJson('/api/v1/usuarios/convites-vinculo',['email'=>'2@example.test','espaco_id'=>5,'perfil'=>'coordenador'])->assertUnprocessable();
  // Contexto antigo também não contorna um conflito criado fora do fluxo permitido.
  $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
  Usuario::find(2)->noVinculo(4);
 }
 public function test_encerrar_preserva_historico_e_libera_novo_grupo():void {
  $this->semExcecao();
  DB::table('usuario_vinculos')->where('id',3)->update(['situacao'=>'encerrado','fim_em'=>now()]);
  Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
  $this->postJson('/api/v1/usuarios/2/encerrar-vinculo',['vinculo_id'=>4])->assertOk();
  $c=app(\App\Services\ConviteVinculo::class)->emitir(Usuario::find(1)->noVinculo(1),'2@example.test',5,'coordenador');
  $this->assertNotNull($c);$this->assertDatabaseHas('usuario_vinculos',['id'=>4,'situacao'=>'encerrado']);
  $this->assertDatabaseHas('vinculo_eventos',['vinculo_id'=>4,'acao'=>'encerrado']);
  $this->postJson('/api/v1/usuarios/2/toggle',['vinculo_id'=>4])->assertUnprocessable();
 }
 public function test_outra_escola_nao_e_bloqueada_pelo_grupo_da_primeira():void {
  $this->semExcecao();
  DB::table('usuario_vinculos')->insert(['id'=>9,'usuario_id'=>1,'espaco_id'=>6,'perfil'=>'dir_escolar','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
  $c=app(\App\Services\ConviteVinculo::class)->emitir(Usuario::find(1)->noVinculo(9),'2@example.test',6,'professor');$this->assertNotNull($c);
 }
 public function test_suporte_registra_excecao_por_chamado_e_revogacao_e_revalidada():void {
  $this->semExcecao();$this->interno();
  $this->postJson('/api/v1/configuracoes/funcoes/consultar',['email'=>'2@example.test','espaco_id'=>5])->assertOk()->assertJsonPath('data.permitir',false);
  $d=['usuario_id'=>2,'espaco_id'=>5,'permitir'=>true,'versao'=>1,'chamado'=>'SUP-FICTICIO-1','motivo'=>'Acumulação autorizada neste chamado fictício'];
  $this->postJson('/api/v1/configuracoes/funcoes',$d)->assertOk()->assertJsonPath('data.versao',2);
  $this->assertDatabaseHas('usuario_funcoes_eventos',['usuario_id'=>2,'espaco_id'=>5,'perfil_ator'=>'suporte','permitir'=>true]);
  $this->postJson('/api/v1/configuracoes/funcoes',$d)->assertStatus(409);
  $this->assertSame('secretario_escolar',Usuario::find(2)->noVinculo(4)->perfil);
  $this->postJson('/api/v1/configuracoes/funcoes',array_replace($d,['permitir'=>false,'versao'=>2]))->assertOk();
  $this->assertDatabaseCount('usuario_funcoes_eventos',2);
  $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);Usuario::find(2)->noVinculo(4);
 }
 public function test_financeiro_e_escola_nao_configuram_excecao():void {
  $this->interno('financeiro');$this->getJson('/api/v1/configuracoes/funcoes/opcoes')->assertForbidden();
  app('auth')->forgetGuards();Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
  $this->getJson('/api/v1/configuracoes/funcoes/opcoes')->assertForbidden();
 }
 public function test_dev_precisa_chamado_e_nao_autoriza_a_si_mesmo():void {
  $this->interno('desenvolvedor');
  $this->postJson('/api/v1/configuracoes/funcoes',['usuario_id'=>2,'espaco_id'=>5,'permitir'=>true,'versao'=>1])->assertUnprocessable();
  $this->postJson('/api/v1/configuracoes/funcoes',['usuario_id'=>1,'espaco_id'=>5,'permitir'=>true,'versao'=>0,'chamado'=>'SUP-2','motivo'=>'Motivo fictício da solicitação'])->assertForbidden();
 }
 public function test_eventos_da_excecao_nao_podem_ser_alterados():void {
  $this->interno();$this->postJson('/api/v1/configuracoes/funcoes',['usuario_id'=>2,'espaco_id'=>5,'permitir'=>true,'versao'=>1,'chamado'=>'SUP-3','motivo'=>'Motivo fictício da autorização'])->assertOk();
  $this->expectException(\Illuminate\Database\QueryException::class);DB::table('usuario_funcoes_eventos')->update(['motivo'=>'Alterado']);
 }
}
