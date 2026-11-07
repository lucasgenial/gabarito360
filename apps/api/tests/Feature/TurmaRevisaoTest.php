<?php
namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Support\Facades\{DB,Process};
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TurmaRevisaoTest extends TestCase
{
    use \Tests\Support\BancoCurriculoDescartavel,\Tests\Support\CenarioDisciplinas;

    private function historico(): void
    {
        DB::table('turmas')->insert(['id'=>3,'escola_id'=>1,'espaco_id'=>5,'nome'=>'1B','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
        DB::table('alunos')->insert(['id'=>1,'turma_id'=>1,'espaco_id'=>5,'nome'=>'Aluno fictício','matricula'=>'F1']);
        DB::table('provas')->where('id',1)->update(['status'=>'corrigida','data_aplicacao'=>'2026-11-03','nota_maxima'=>10,'num_questoes'=>10,'perfil_criacao'=>'coordenador']);
        DB::table('prova_turmas')->insert(['prova_id'=>1,'turma_id'=>1,'espaco_id'=>5]);
        DB::table('cartoes')->insert(['id'=>1,'prova_id'=>1,'aluno_id'=>1,'espaco_id'=>5,'status'=>'lido']);
        DB::table('notas')->insert(['cartao_id'=>1,'prova_id'=>1,'aluno_id'=>1,'turma_id'=>1,'espaco_id'=>5,'acertos'=>8,'total_questoes'=>10,'nota_final'=>8,'status_aprovacao'=>'aprovado']);
    }

    private function pedido(array $m=[]): array { return array_replace(['turma_id'=>3,'turma_origem_id'=>1,'chave'=>(string)Str::uuid(),'motivo'=>'Mudança solicitada pela coordenação'],$m); }

    public function test_transferencia_preserva_notas_imagens_e_coorte_com_reenvio_idempotente(): void
    {
        $this->historico();$p=$this->pedido();$antes=DB::table('notas')->get()->toJson();
        $this->postJson('/api/v1/alunos/1/transferencias-turma',$p)->assertOk();
        $this->postJson('/api/v1/alunos/1/transferencias-turma',$p)->assertOk();
        $this->assertDatabaseHas('alunos',['id'=>1,'turma_id'=>3]);
        $this->assertDatabaseHas('cartoes',['id'=>1,'turma_aplicacao_id'=>1]);
        $this->assertSame($antes,DB::table('notas')->get()->toJson());
        $this->assertDatabaseCount('aluno_movimentacoes',1);
        $this->assertDatabaseHas('aluno_movimentacoes',['ator_id'=>1,'ator_vinculo_id'=>1,'turma_origem_id'=>1,'turma_destino_id'=>3]);
        $this->getJson('/api/v1/relatorios/turma/1')->assertOk()->assertJsonPath('data.provas.0.corrigidos',1);
        $this->getJson('/api/v1/relatorios/aluno/1')->assertOk()->assertJsonPath('data.provas.0.nota',8);
        $this->getJson('/api/v1/turmas/1/painel')->assertOk()->assertJsonPath('data.kpis.resultados',1)->assertJsonPath('data.kpis.acerto_pct',80);
        $this->postJson('/api/v1/alunos/1/transferencias-turma',$this->pedido())->assertConflict();
        $this->postJson('/api/v1/alunos/1/transferencias-turma',array_replace($p,['motivo'=>'Outro motivo conflitante']))->assertConflict();
    }

    public function test_transferencia_recusa_outra_escola_inativa_fila_e_usuario_sem_permissao(): void
    {
        $this->historico();
        $this->postJson('/api/v1/alunos/1/transferencias-turma',$this->pedido(['turma_id'=>2]))->assertNotFound();
        DB::table('turmas')->where('id',3)->update(['ativo'=>false]);
        $this->postJson('/api/v1/alunos/1/transferencias-turma',$this->pedido())->assertNotFound();
        DB::table('turmas')->where('id',3)->update(['ativo'=>true]);
        DB::table('cartoes')->where('id',1)->update(['status'=>'pendente']);
        $this->postJson('/api/v1/alunos/1/transferencias-turma',$this->pedido())->assertConflict();
        $this->assertDatabaseHas('alunos',['id'=>1,'turma_id'=>1]);$this->assertDatabaseCount('aluno_movimentacoes',0);
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(2)->noVinculo(3));
        $this->postJson('/api/v1/alunos/1/transferencias-turma',$this->pedido())->assertForbidden();
    }

    public function test_update_existente_tambem_preserva_contexto_e_historico_nao_pode_ser_reescrito(): void
    {
        $this->historico();$this->putJson('/api/v1/alunos/1',['turma_id'=>3])->assertOk();
        $this->assertDatabaseHas('cartoes',['id'=>1,'turma_aplicacao_id'=>1]);$this->assertDatabaseCount('aluno_movimentacoes',1);
        $this->expectException(\Illuminate\Database\QueryException::class);DB::table('aluno_movimentacoes')->delete();
    }

    public function test_painel_filtra_aplicacao_curriculo_e_autoria_original_sem_vazar_opcoes(): void
    {
        $this->historico();
        $d=$this->postJson('/api/v1/disciplinas',['nome'=>'Matemática','ativo'=>true])->assertCreated()->json('data.id');
        DB::table('prova_curriculos')->insert(['prova_id'=>1,'espaco_id'=>5,'ator_vinculo_id'=>1]);
        DB::table('prova_disciplinas')->insert(['prova_id'=>1,'disciplina_id'=>$d,'espaco_id'=>5,'nome'=>'Matemática']);
        $this->getJson('/api/v1/turmas/1/painel?disciplina_id='.$d.'&inicio=2026-11-01&fim=2026-11-03')->assertOk()->assertJsonPath('data.kpis.avaliacoes',1)->assertJsonCount(0,'data.opcoes.professores');
        $this->getJson('/api/v1/turmas/1/painel?inicio=2026-11-04')->assertOk()->assertJsonPath('data.kpis.resultados',0)->assertJsonPath('data.kpis.acerto_pct',null);
        $this->getJson('/api/v1/turmas/1/painel?professor_id=999')->assertUnprocessable();
        $this->getJson('/api/v1/turmas/2/painel')->assertNotFound();
        DB::table('provas')->where('id',1)->update(['criado_por'=>2,'perfil_criacao'=>'professor','vinculo_criacao_id'=>3]);
        $this->getJson('/api/v1/turmas/1/painel?professor_id=2')->assertOk()->assertJsonPath('data.opcoes.professores.0.nome','Fictício 2')->assertJsonPath('data.kpis.resultados',1);
    }

    public function test_pdf_usa_mesmo_recorte_orientacao_e_registro_verificavel(): void
    {
        $this->historico();config(['documentos.validacao_url'=>'https://example.test/validar-documento']);$arquivos=[];
        Process::fake(function($p)use(&$arquivos){$d=json_decode($p->input,true);$this->assertSame('paisagem',$d['orientacao']);$this->assertSame(1,$d['kpis']['resultados']);$this->assertSame('Fictício 1',$d['emissao']['emissor']);$arquivos[]=$d['saida'];file_put_contents($d['saida'],'%PDF-ficticio');return Process::result(output:'{"ok":true}');});
        try{$this->getJson('/api/v1/turmas/1/painel/pdf?orientacao=paisagem&fim=2026-11-03')->assertOk()->assertHeader('Content-Type','application/pdf');$this->assertDatabaseHas('documento_emissoes',['modelo'=>'painel_turma','emissor_id'=>1]);}
        finally{foreach($arquivos as $f)if(is_file($f))unlink($f);}
    }

    public function test_secretaria_transfere_sem_receber_dados_pedagogicos_e_retirada_bloqueia(): void
    {
        $this->historico();
        Usuario::forceCreate(['id'=>4,'perfil'=>'professor','nome'=>'Secretaria fictícia','email'=>'sec@example.test','cpf'=>'00000000004','password'=>'ficticio','ativo'=>true]);
        DB::table('usuario_vinculos')->insert(['id'=>6,'usuario_id'=>4,'espaco_id'=>5,'perfil'=>'secretario_escolar','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(4)->noVinculo(6));
        $this->getJson('/api/v1/turmas/1/painel')->assertForbidden();
        $this->getJson('/api/v1/turmas/1/painel/pdf')->assertForbidden();
        $this->postJson('/api/v1/alunos/1/transferencias-turma',$this->pedido())->assertOk();
        $this->getJson('/api/v1/turmas/3')->assertOk()->assertJsonMissingPath('data.media_turma')->assertJsonMissingPath('data.alunos.0.media_geral');
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>6,'permissao'=>'aluno.gerir','efeito'=>'retirar']);
        $this->postJson('/api/v1/alunos/1/transferencias-turma',$this->pedido(['turma_origem_id'=>3,'turma_id'=>1]))->assertForbidden();
    }

    public function test_professor_de_origem_conserva_apenas_resultados_autorizados_apos_transferencia(): void
    {
        $this->historico();
        DB::table('provas')->where('id',1)->update(['criado_por'=>2,'perfil_criacao'=>'professor','vinculo_criacao_id'=>3]);
        $this->postJson('/api/v1/alunos/1/transferencias-turma',$this->pedido())->assertOk();
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(2)->noVinculo(3));
        $this->getJson('/api/v1/relatorios/turma/1')->assertOk()->assertJsonPath('data.provas.0.corrigidos',1);
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertOk()->assertJsonPath('data.aluno.turma_id',1);
        $this->getJson('/api/v1/alunos/1')->assertNotFound();
        $this->getJson('/api/v1/turmas/3/painel')->assertNotFound();
        DB::table('vinculo_turmas')->where('vinculo_id',3)->delete();
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertNotFound();
    }
}
