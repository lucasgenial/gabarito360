<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Escopo;
use App\Services\GestaoDisciplinas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArquivamentoCadastroTest extends TestCase
{
 use \Tests\Support\BancoCurriculoDescartavel,\Tests\Support\CenarioDisciplinas;
 private function aluno():void { DB::table('alunos')->insert(['id'=>1,'turma_id'=>1,'espaco_id'=>5,'nome'=>'Aluno fictício','matricula'=>'F1']); }
 private function pedido(bool $arquivado=true,bool $ativo=true,int $versao=0):array {return ['arquivado'=>$arquivado,'esperado_ativo'=>$ativo,'versao'=>$versao,'chave'=>(string)Str::uuid(),'motivo'=>'Organização cadastral'];}
 private function entrar(int $u,int $v):void {app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail($u)->noVinculo($v));}
 private function turmaExtra():void {DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'turma.desativar','efeito'=>'conceder']);}

 public function test_intencao_explicita_idempotencia_revisao_e_aba_antiga_nao_invertem_estado():void {
  $this->aluno();$p=$this->pedido();
  $this->putJson('/api/v1/alunos/1/arquivamento',$p)->assertOk()->assertJsonPath('data.ativo',false)->assertJsonPath('data.versao',1);
  $this->putJson('/api/v1/alunos/1/arquivamento',$p)->assertOk()->assertJsonPath('data.repetido',true);
  $this->assertDatabaseCount('cadastro_arquivamentos',1);
  $this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido(false,false,1))->assertOk()->assertJsonPath('data.versao',2);
  $this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido(true,true,0))->assertConflict(); // ABA: booleano voltou, revisão não.
  $this->putJson('/api/v1/alunos/1/arquivamento',$p)->assertOk()->assertJsonPath('data.repetido',true);
  $this->assertDatabaseHas('alunos',['id'=>1,'ativo'=>true]);$this->assertDatabaseCount('cadastro_arquivamentos',2);
  $this->putJson('/api/v1/alunos/1/arquivamento',array_replace($p,['motivo'=>'Outro pedido']))->assertConflict();
  $this->getJson('/api/v1/alunos/1')->assertOk()->assertJsonPath('data.arquivamento.versao',2);
 }
 public function test_noop_gera_recibo_sem_nova_transicao_e_evento_e_imutavel():void {
  $this->aluno();$this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido())->assertOk();
  $this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido(true,false,1))->assertOk()->assertJsonPath('data.alterado',false)->assertJsonPath('data.versao',1);
  $this->assertDatabaseCount('cadastro_arquivamentos',2);$this->assertSame(1,DB::table('cadastro_arquivamentos')->whereNotNull('sequencia')->count());
  $this->assertStringNotContainsString('Organização cadastral',DB::table('cadastro_arquivamentos')->value('motivo'));
  $this->expectException(\Illuminate\Database\QueryException::class);DB::table('cadastro_arquivamentos')->update(['versao'=>999]);
 }
 public function test_permissao_de_gerir_nao_permite_mudar_estado_da_turma_por_put_store_ou_toggle():void {
  $this->putJson('/api/v1/turmas/1/arquivamento',$this->pedido())->assertForbidden();
  $this->putJson('/api/v1/turmas/1',['ativo'=>false])->assertForbidden();
  $this->postJson('/api/v1/turmas/1/toggle')->assertForbidden();
  $this->postJson('/api/v1/turmas',['nome'=>'Nova','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026,'escola_id'=>1,'ativo'=>false])->assertForbidden();
  $this->putJson('/api/v1/turmas/1',['nome'=>'Nome ajustado'])->assertOk();
  $this->assertDatabaseHas('turmas',['id'=>1,'ativo'=>true]);$this->assertDatabaseCount('cadastro_arquivamentos',0);
 }
 public function test_arquivar_turma_nao_altera_alunos_vinculos_ou_provas_e_put_aluno_tambem_audita():void {
  $this->aluno();$this->turmaExtra();$provas=DB::table('provas')->get()->toJson();$vinculos=DB::table('usuario_vinculos')->get()->toJson();
  $this->putJson('/api/v1/turmas/1/arquivamento',$this->pedido())->assertOk();
  $this->assertDatabaseHas('alunos',['id'=>1,'ativo'=>true,'turma_id'=>1]);
  $this->assertSame($provas,DB::table('provas')->get()->toJson());$this->assertSame($vinculos,DB::table('usuario_vinculos')->get()->toJson());
  $this->putJson('/api/v1/alunos/1',['ativo'=>false])->assertOk()->assertJsonPath('data.arquivamento.versao',1);
  $this->assertDatabaseCount('cadastro_arquivamentos',2);
 }
 public function test_escopo_payload_e_revogacao_sao_revalidados_inclusive_em_replay():void {
  $this->aluno();$p=$this->pedido();
  $this->putJson('/api/v1/turmas/2/arquivamento',$p)->assertForbidden(); // Sem permissão de turma, antes de lookup.
  $this->turmaExtra();$this->putJson('/api/v1/turmas/2/arquivamento',$p)->assertNotFound();
  $this->putJson('/api/v1/alunos/1/arquivamento',array_replace($p,['espaco_id'=>6]))->assertUnprocessable();
  $this->putJson('/api/v1/alunos/1/arquivamento',array_diff_key($p,['versao'=>true]))->assertUnprocessable();
  $this->putJson('/api/v1/alunos/1/arquivamento',$p)->assertOk();
  DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'aluno.desativar','efeito'=>'retirar']);
  $this->putJson('/api/v1/alunos/1/arquivamento',$p)->assertForbidden();
  $this->assertDatabaseCount('cadastro_arquivamentos',1);
 }
 public function test_professor_com_gerir_extra_nao_arquiva_cadastro():void {
  $this->aluno();DB::table('vinculo_permissoes')->insert(['vinculo_id'=>3,'permissao'=>'aluno.gerir','efeito'=>'conceder']);$this->entrar(2,3);
  $this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido())->assertForbidden();
  $this->putJson('/api/v1/alunos/1',['ativo'=>false])->assertForbidden();
  $this->putJson('/api/v1/alunos/1',['nome'=>'Nome retificado'])->assertOk();
 }
 public function test_falha_do_recibo_reverte_tambem_estado():void {
  $this->aluno();DB::unprepared(DB::getDriverName()==='sqlite'?"CREATE TRIGGER recusar_recibo BEFORE INSERT ON cadastro_arquivamentos BEGIN SELECT RAISE(ABORT,'Falha ficticia'); END":"CREATE TRIGGER recusar_recibo BEFORE INSERT ON cadastro_arquivamentos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Falha ficticia'");
  $this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido())->assertStatus(500);
  $this->assertDatabaseHas('alunos',['id'=>1,'ativo'=>true]);$this->assertDatabaseCount('cadastro_arquivamentos',0);
 }
 public function test_toggle_legado_com_estado_esperado_conserva_contrato_e_recusa_repeticao_cega_da_aba():void {
  $this->aluno();$this->postJson('/api/v1/alunos/1/toggle',['esperado_ativo'=>true])->assertOk()->assertJsonPath('data.ativo',false);
  $this->postJson('/api/v1/alunos/1/toggle',['esperado_ativo'=>true])->assertConflict();
  $this->postJson('/api/v1/alunos/1/toggle')->assertOk()->assertJsonPath('data.ativo',true); // Compatibilidade antiga, ainda alterna sem chave.
 }
 public function test_arquivada_recusa_novo_aluno_sem_reativar_turma_ou_registros():void {
  $this->aluno();$this->turmaExtra();$this->putJson('/api/v1/turmas/1/arquivamento',$this->pedido())->assertOk();
  $this->postJson('/api/v1/alunos',['turma_id'=>1,'nome'=>'Novo aluno','matricula'=>'F2','data_nascimento'=>'2010-01-01'])->assertNotFound();
  $this->assertDatabaseCount('alunos',1);
 }
 public function test_secretaria_arquiva_cadastros_sem_receber_notas_ou_painel():void {
  $this->aluno();Usuario::forceCreate(['id'=>4,'perfil'=>'professor','nome'=>'Secretaria fictícia','email'=>'sec@example.test','cpf'=>'00000000004','password'=>'ficticio','ativo'=>true]);
  DB::table('usuario_vinculos')->insert(['id'=>6,'usuario_id'=>4,'espaco_id'=>5,'perfil'=>'secretario_escolar','situacao'=>'ativo','inicio_em'=>now()->subDay()]);$this->entrar(4,6);
  $this->putJson('/api/v1/turmas/1/arquivamento',$this->pedido())->assertOk();
  $this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido())->assertOk();
  $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonMissingPath('data.media_turma');
  $this->getJson('/api/v1/turmas/1/painel')->assertForbidden();$this->getJson('/api/v1/relatorios/turma/1')->assertForbidden();
 }
 public function test_leitura_historica_preserva_acl_curricular_sem_soltar_escopo_operacional():void {
  $u=Usuario::findOrFail(1)->noVinculo(1);$g=app(GestaoDisciplinas::class);
  $d=$g->salvar($u,['nome'=>'Matemática','ativo'=>true]);$g->indicar($u,$d->id,['vinculo_id'=>3,'ativo'=>true]);$c=$g->adicionarComponente($u,1,$d->id);
  $g->atribuir($u,1,$c->id,['vinculo_id'=>3,'inicio_em'=>'2026-11-04','fim_em'=>null,'papel'=>'titular','chave'=>(string)Str::uuid(),'versao'=>1]);
  DB::table('provas')->where('id',1)->update(['perfil_criacao'=>'coordenador','status'=>'corrigida','data_aplicacao'=>'2026-11-03']);
  DB::table('prova_curriculos')->insert(['prova_id'=>1,'espaco_id'=>5,'ator_vinculo_id'=>1]);
  DB::table('prova_turmas')->insert(['prova_id'=>1,'turma_id'=>1,'espaco_id'=>5]);DB::table('prova_disciplinas')->insert(['prova_id'=>1,'disciplina_id'=>$d->id,'espaco_id'=>5,'nome'=>'Matemática']);
  $this->turmaExtra();$this->putJson('/api/v1/turmas/1/arquivamento',$this->pedido())->assertOk();$this->entrar(2,3);$prof=Usuario::findOrFail(2)->noVinculo(3);
  $this->assertFalse(Escopo::provas($prof)->whereKey(1)->exists());$this->assertTrue(Escopo::provasHistoricas($prof)->whereKey(1)->exists());
  $this->getJson('/api/v1/relatorios/prova/1')->assertOk();
  $this->aluno();\Illuminate\Support\Facades\Storage::fake('public');
  \Illuminate\Support\Facades\Storage::disk('public')->put('arquivo-ficticio.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aQX8AAAAASUVORK5CYII='));
  DB::table('cartoes')->insert(['id'=>1,'prova_id'=>1,'aluno_id'=>1,'espaco_id'=>5,'status'=>'lido','imagem_url'=>'arquivo-ficticio.png','turma_aplicacao_id'=>1]);
  $foto=$this->get('/api/v1/cartoes/1/imagens/original')->assertOk();$this->assertStringContainsString('no-store',$foto->headers->get('Cache-Control'));
  DB::table('notas')->insert(['cartao_id'=>1,'prova_id'=>1,'aluno_id'=>1,'espaco_id'=>5,'turma_id'=>1,'acertos'=>1,'total_questoes'=>1,'nota_final'=>10,'status_aprovacao'=>'aprovado']);
  $gb=DB::table('gabaritos')->insertGetId(['prova_id'=>1,'espaco_id'=>5]);DB::table('gabarito_questoes')->insert(['gabarito_id'=>$gb,'espaco_id'=>5,'numero_questao'=>1,'alternativa'=>'A','peso'=>1]);
  DB::table('cartao_respostas')->insert(['cartao_id'=>1,'espaco_id'=>5,'numero_questao'=>1,'alternativa'=>'A']);
  $this->getJson('/api/v1/alunos/1/resultados/1')->assertOk()->assertJsonPath('data.nota.nota_final',10);
  \Illuminate\Support\Facades\Storage::fake('local');
  $tentativa=app(\App\Services\Omr\HistoricoCartao::class)->garantir(\App\Models\Cartao::findOrFail(1),$prof);
  $this->getJson('/api/v1/provas/1/tentativas?aluno_id=1')->assertOk()->assertJsonPath('data.total',1);
  $this->getJson('/api/v1/tentativas/'.$tentativa->id)->assertOk()->assertJsonPath('data.pode_aplicar',false);
  $this->get('/api/v1/tentativas/'.$tentativa->id.'/imagem')->assertOk();
  $this->postJson('/api/v1/tentativas/'.$tentativa->id.'/aplicar',['motivo'=>'Solicitação não autorizada','confirmar'=>true])->assertNotFound();
  DB::table('docente_atribuicoes')->where('vinculo_id',3)->update(['situacao'=>'encerrada']);
  $this->assertFalse(Escopo::provasHistoricas($prof)->whereKey(1)->exists());
  $this->get('/api/v1/cartoes/1/imagens/original')->assertNotFound();
 }
 public function test_vice_exige_extra_e_diretor_tem_o_estado_sem_somar_perfis():void {
  $this->aluno();DB::table('usuario_vinculos')->insert(['id'=>6,'usuario_id'=>3,'espaco_id'=>5,'perfil'=>'vice_dir_escolar','situacao'=>'ativo','inicio_em'=>now()->subDay()]);$this->entrar(3,6);
  $this->putJson('/api/v1/turmas/1/arquivamento',$this->pedido())->assertForbidden();$this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido())->assertForbidden();
  foreach(['turma.desativar','aluno.desativar']as$p)DB::table('vinculo_permissoes')->insert(['vinculo_id'=>6,'permissao'=>$p,'efeito'=>'conceder']);
  $this->putJson('/api/v1/turmas/1/arquivamento',$this->pedido())->assertOk();$this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido())->assertOk();
  $this->entrar(1,4);$this->putJson('/api/v1/turmas/1/arquivamento',$this->pedido(false,false,1))->assertOk();
 }
 public function test_autonomo_arquiva_apenas_seu_espaco_pessoal_sem_dados_institucionais():void {
  config(['espacos.pessoal'=>true]);
  DB::table('clientes')->insert(['id'=>9,'tipo'=>'professor','nome'=>'Pessoal fictício','modalidade'=>'interno','situacao'=>'ativo']);
  DB::table('espacos')->insert(['id'=>9,'cliente_id'=>9,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>3]);
  DB::table('usuario_vinculos')->insert(['id'=>9,'usuario_id'=>3,'espaco_id'=>9,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
  DB::table('turmas')->insert(['id'=>9,'espaco_id'=>9,'espaco_tipo'=>'pessoal','nome'=>'Minha turma','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
  DB::table('alunos')->insert(['id'=>9,'espaco_id'=>9,'espaco_tipo'=>'pessoal','turma_id'=>9,'nome'=>'Cadastro mínimo','matricula'=>'P9']);$this->entrar(3,9);
  $this->putJson('/api/v1/turmas/9/arquivamento',$this->pedido())->assertOk();$this->putJson('/api/v1/alunos/9/arquivamento',$this->pedido())->assertOk();
  $this->putJson('/api/v1/turmas/1/arquivamento',$this->pedido())->assertNotFound();
  $this->getJson('/api/v1/alunos/9')->assertOk()->assertJsonMissingPath('data.cpf')->assertJsonPath('data.arquivamento.versao',1);
 }
 public function test_auxiliar_aplicador_e_aluno_nao_arquivam_e_recurso_nao_e_inferido_do_corpo():void {
  $this->aluno();
  foreach(['auxiliar_secretaria','aplicador','aluno']as$i=>$perfil){
   $id=10+$i;Usuario::forceCreate(['id'=>$id,'perfil'=>'professor','nome'=>'Perfil fictício','email'=>$id.'@example.test','cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
   DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>5,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay()]);$this->entrar($id,$id);
   foreach(['turmas','alunos']as$tipo)$this->putJson('/api/v1/'.$tipo.'/1/arquivamento',$this->pedido())->assertForbidden();
  }
  $this->assertDatabaseCount('cadastro_arquivamentos',0);
 }
 public function test_dto_aluno_dentro_da_turma_contem_versao_sem_modificar_historico():void {
  $this->aluno();$this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido())->assertOk();
  $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.alunos.0.arquivamento.versao',1)->assertJsonPath('data.alunos.0.arquivamento.arquivado',true);
  $this->getJson('/api/v1/alunos')->assertOk()->assertJsonPath('data.alunos.0.arquivamento.versao',1);
 }
 public function test_importacao_nao_desarquiva_aluno_turma_ou_transfere_implicitamente():void {
  $this->aluno();$csv=tempnam(sys_get_temp_dir(),'g360_csv_');
  $args=['escola_id'=>1,'arquivo'=>$csv,'--usuario'=>1,'--vinculo'=>1,'--espaco'=>5,'--aplicar'=>true,'--ano'=>2026];
  try{
   file_put_contents($csv,"CLASS;NAME;MATRICULA\n1A;Nome corrigido;F1\n");
   $this->artisan('g360:importar-alunos',$args)->assertSuccessful();
   $this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido())->assertOk();
   $this->artisan('g360:importar-alunos',$args)->expectsOutput('Desarquive o cadastro do aluno pelo fluxo próprio antes de importar.')->assertFailed();
   $this->assertDatabaseHas('alunos',['id'=>1,'ativo'=>false]);$this->assertDatabaseCount('cadastro_arquivamentos',1);
   $this->putJson('/api/v1/alunos/1/arquivamento',$this->pedido(false,false,1))->assertOk();
   DB::table('turmas')->insert(['id'=>3,'escola_id'=>1,'espaco_id'=>5,'nome'=>'1B','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
   file_put_contents($csv,"CLASS;NAME;MATRICULA\n1B;Outro nome;F1\n");
   $this->artisan('g360:importar-alunos',$args)->expectsOutput('A importação não transfere alunos entre turmas. Utilize a transferência de turma.')->assertFailed();
   $this->assertDatabaseHas('alunos',['id'=>1,'turma_id'=>1,'nome'=>'Nome corrigido']);
   $this->turmaExtra();$this->putJson('/api/v1/turmas/3/arquivamento',$this->pedido())->assertOk();
   file_put_contents($csv,"CLASS;NAME;MATRICULA\n1B;Novo cadastro;F2\n");
   $this->artisan('g360:importar-alunos',$args)->expectsOutput('Desarquive a turma antes de importar alunos.')->assertFailed();
   $this->assertDatabaseCount('alunos',1);
  }finally{unlink($csv);}
 }
 public function test_novas_associacoes_prova_professor_e_importador_nao_usam_turma_arquivada():void {
  $this->turmaExtra();$this->putJson('/api/v1/turmas/1/arquivamento',$this->pedido())->assertOk();
  $this->postJson('/api/v1/turmas/1/professores',['usuario_id'=>3,'vinculo_id'=>5])->assertUnprocessable();
  $this->assertDatabaseMissing('vinculo_turmas',['vinculo_id'=>5,'turma_id'=>1]);
  $this->postJson('/api/v1/provas',['escola_id'=>1,'titulo'=>'Não associar','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4,'turma_ids'=>[1]])->assertUnprocessable();
  $json=tempnam(sys_get_temp_dir(),'g360_prova_');
  try{
   file_put_contents($json,json_encode(['titulo'=>'Não importar','serie'=>'1','provas'=>[['disciplina'=>'Matemática','gabarito'=>'A']]]));
   $this->artisan('g360:importar-provas',['arquivo'=>$json,'--escola'=>1,'--usuario'=>1,'--vinculo'=>1,'--espaco'=>5,'--turmas'=>'1A','--ano'=>2026,'--aplicar'=>true])->assertFailed();
   $this->assertDatabaseMissing('provas',['titulo'=>'Não importar']);
  }finally{unlink($json);}
 }
}
