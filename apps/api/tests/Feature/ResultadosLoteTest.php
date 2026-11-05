<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Process};
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResultadosLoteTest extends TestCase
{
    use RefreshDatabase;
    private array $arquivos = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true]);
        Permissao::limparCache();
        foreach ([1,2] as $id) {
            Usuario::forceCreate(['id'=>$id,'perfil'=>'professor','nome'=>'Professor fictício','email'=>"lote$id@example.test",'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Cliente','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>"Pessoal $id",'situacao'=>'ativo','usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
            DB::table('turmas')->insert(['id'=>$id,'espaco_id'=>$id,'espaco_tipo'=>'pessoal','nome'=>"Turma $id",'serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
            DB::table('provas')->insert(['id'=>$id,'espaco_id'=>$id,'espaco_tipo'=>'pessoal','criado_por'=>$id,'titulo'=>'Simulado','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4]);
            DB::table('prova_turmas')->insert(['prova_id'=>$id,'turma_id'=>$id,'espaco_id'=>$id]);
            DB::table('gabaritos')->insert(['id'=>$id,'prova_id'=>$id,'espaco_id'=>$id]);
            DB::table('gabarito_questoes')->insert(['gabarito_id'=>$id,'espaco_id'=>$id,'numero_questao'=>1,'alternativa'=>'A','peso'=>1]);
            $this->aluno($id,$id,$id);
        }
        $this->aluno(3,1,1); // Dois resultados, além de um aluno ainda sem correção.
        DB::table('alunos')->insert(['id'=>4,'turma_id'=>1,'espaco_id'=>1,'espaco_tipo'=>'pessoal','nome'=>'Sem resultado','matricula'=>'P4']);
        Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
    }

    private function aluno(int $id, int $espaco, int $prova): void
    {
        DB::table('alunos')->insert(['id'=>$id,'turma_id'=>$espaco,'espaco_id'=>$espaco,'espaco_tipo'=>'pessoal','nome'=>"Aluno $id",'matricula'=>"P$id"]);
        DB::table('cartoes')->insert(['id'=>$id,'prova_id'=>$prova,'aluno_id'=>$id,'espaco_id'=>$espaco,'imagem_url'=>'ficticio.jpg','status'=>'lido']);
        DB::table('cartao_respostas')->insert(['cartao_id'=>$id,'espaco_id'=>$espaco,'numero_questao'=>1,'alternativa'=>'A']);
        DB::table('notas')->insert(['cartao_id'=>$id,'aluno_id'=>$id,'prova_id'=>$prova,'turma_id'=>$espaco,'espaco_id'=>$espaco,'nota_final'=>10,'acertos'=>1,'total_questoes'=>1,'status_aprovacao'=>'aprovado']);
    }

    private function gerador(?callable $durante = null): void
    {
        Process::fake(function ($p) use ($durante) {
            $d=json_decode($p->input,true); $this->arquivos[]=$d['saida'];
            $this->assertSame(['Aluno 1','Aluno 3'],array_column(array_column($d['resultados'],'aluno'),'nome'));
            $this->assertSame('A',$d['resultados'][0]['folha'][0]['marcada']);
            $this->assertSame('Pessoal 1',$d['resultados'][0]['escola']);
            file_put_contents($d['saida'],'%PDF-ficticio');
            if ($durante) $durante();
            return Process::result(output:json_encode(['ok'=>true,'total'=>2]));
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->arquivos as $p) if (is_file($p)) unlink($p);
        parent::tearDown();
    }

    public function test_lote_reune_apenas_resultados_autorizados_sem_alterar_gabarito(): void
    {
        $antes=DB::table('gabarito_questoes')->get()->toJson();
        $this->gerador();
        $this->getJson('/api/v1/provas/1/resultados/pdf?turma_id=1')->assertOk()->assertHeader('Content-Type','application/pdf');
        Process::assertRanTimes(fn()=>true,1);
        $this->assertSame($antes,DB::table('gabarito_questoes')->get()->toJson());
        $this->assertDatabaseCount('notas',3);
    }

    public function test_outro_espaco_turma_invalida_e_lote_vazio_nao_geram_documento(): void
    {
        Process::fake();
        $this->getJson('/api/v1/provas/2/resultados/pdf')->assertNotFound();
        $this->getJson('/api/v1/provas/1/resultados/pdf?turma_id=2')->assertNotFound();
        $this->getJson('/api/v1/provas/1/resultados/pdf?turma_id=abc')->assertUnprocessable();
        DB::table('notas')->where('prova_id',1)->delete();
        $this->getJson('/api/v1/provas/1/resultados/pdf')->assertUnprocessable();
        Process::assertNothingRan();
    }

    public function test_revogacao_durante_geracao_apaga_temporario_e_recusa_entrega(): void
    {
        $this->gerador(fn()=>DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']));
        $this->getJson('/api/v1/provas/1/resultados/pdf')->assertForbidden();
        $this->assertFileDoesNotExist($this->arquivos[0]);
        $this->assertDatabaseCount('documento_emissoes', 0);
    }

    public function test_falha_do_gerador_remove_arquivo_parcial(): void
    {
        Process::fake(function ($p) {
            $d=json_decode($p->input,true);$this->arquivos[]=$d['saida'];file_put_contents($d['saida'],'parcial');
            return Process::result(output:'{"ok":false}',exitCode:1);
        });
        $this->getJson('/api/v1/provas/1/resultados/pdf')->assertStatus(500);
        $this->assertFileDoesNotExist($this->arquivos[0]);
    }

    public function test_professor_escolar_so_inclui_suas_turmas_e_aplicador_nao_tem_lote(): void
    {
        DB::table('clientes')->insert(['id'=>10,'tipo'=>'rede','nome'=>'Rede fictícia','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('redes')->insert(['id'=>10,'nome'=>'Rede fictícia','tipo'=>'estadual','uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>10,'rede_id'=>10,'nome'=>'Núcleo fictício']);
        DB::table('escolas')->insert(['id'=>10,'nucleo_id'=>10,'nome'=>'Escola fictícia','inep'=>'00000010','tipo_rede'=>'estadual']);
        foreach (['rede'=>10,'nucleo'=>11,'escola'=>12] as $tipo=>$id) DB::table('espacos')->insert([
            'id'=>$id,'cliente_id'=>10,'tipo'=>$tipo,'nome'=>'Espaço fictício','situacao'=>'ativo',$tipo.'_id'=>10]);
        DB::table('usuario_vinculos')->insert(['id'=>10,'usuario_id'=>1,'espaco_id'=>12,'perfil'=>'professor','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        DB::table('provas')->insert(['id'=>10,'escola_id'=>10,'espaco_id'=>12,'criado_por'=>1,'titulo'=>'Prova escolar','disciplina'=>'Teste','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4]);
        DB::table('gabaritos')->insert(['id'=>10,'prova_id'=>10,'espaco_id'=>12]);
        DB::table('gabarito_questoes')->insert(['gabarito_id'=>10,'espaco_id'=>12,'numero_questao'=>1,'alternativa'=>'A','peso'=>1]);
        foreach ([10,11] as $id) {
            DB::table('turmas')->insert(['id'=>$id,'escola_id'=>10,'espaco_id'=>12,'nome'=>"Turma $id",'serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
            DB::table('prova_turmas')->insert(['prova_id'=>10,'turma_id'=>$id,'espaco_id'=>12]);
            DB::table('alunos')->insert(['id'=>$id,'turma_id'=>$id,'espaco_id'=>12,'nome'=>"Escolar $id",'matricula'=>"ESC$id"]);
            DB::table('cartoes')->insert(['id'=>$id,'prova_id'=>10,'aluno_id'=>$id,'espaco_id'=>12,'imagem_url'=>'ficticio.jpg','status'=>'lido']);
            DB::table('notas')->insert(['cartao_id'=>$id,'aluno_id'=>$id,'prova_id'=>10,'turma_id'=>$id,'espaco_id'=>12,'nota_final'=>0,'acertos'=>0,'total_questoes'=>1,'status_aprovacao'=>'recuperacao']);
        }
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>10,'espaco_id'=>12,'turma_id'=>10]);
        Sanctum::actingAs(Usuario::find(1)->noVinculo(10));
        DB::table('cartao_respostas')->insert(['cartao_id'=>10,'espaco_id'=>12,'numero_questao'=>1,'alternativa'=>'A']);
        DB::table('cartao_respostas')->insert(['cartao_id'=>11,'espaco_id'=>12,'numero_questao'=>1,'alternativa'=>null]);
        $this->getJson('/api/v1/alunos/10/resultados/10')->assertOk()
            ->assertJsonPath('data.folha.0.turma.percentual',100)->assertJsonPath('data.folha.0.geral.percentual',50)
            ->assertJsonPath('data.folha.0.geral.total',2);
        $this->getJson('/api/v1/alunos/11/resultados/10')->assertNotFound();
        Process::fake(function ($p) {
            $d=json_decode($p->input,true);$this->arquivos[]=$d['saida'];
            $this->assertSame(['Escolar 10'],array_column(array_column($d['resultados'],'aluno'),'nome'));
            file_put_contents($d['saida'],'%PDF-ficticio');
            return Process::result(output:'{"ok":true,"total":1}');
        });
        $this->getJson('/api/v1/provas/10/resultados/pdf')->assertOk();
        $this->getJson('/api/v1/provas/10/resultados/pdf?turma_id=11')->assertNotFound();
        DB::table('usuario_vinculos')->where('id',10)->update(['perfil'=>'aplicador']);
        Sanctum::actingAs(Usuario::find(1)->noVinculo(10));
        $this->getJson('/api/v1/provas/10/resultados/pdf')->assertForbidden();
        Process::assertRanTimes(fn()=>true,1);
    }

    public function test_estatisticas_respeitam_lingua_anulada_ausente_e_outro_espaco(): void
    {
        DB::table('provas')->where('id',1)->update(['opcao_lingua'=>true]);
        DB::table('gabarito_questoes')->where('gabarito_id',1)->update(['alternativa_espanhol'=>'B']);
        DB::table('cartoes')->where('id',3)->update(['lingua'=>'ESP']);
        DB::table('cartao_respostas')->where('cartao_id',3)->update(['alternativa'=>'B']);
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertOk()->assertJsonPath('data.pedagogico',true)
            ->assertJsonPath('data.folha.0.geral.total',2)->assertJsonPath('data.folha.0.geral.percentual',100);
        DB::table('cartao_respostas')->where('cartao_id',3)->update(['alternativa'=>null]);
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertOk()->assertJsonPath('data.folha.0.geral.percentual',50);
        DB::table('gabarito_questoes')->where('gabarito_id',1)->update(['anulada'=>true]);
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertOk()->assertJsonPath('data.folha.0.geral.percentual',null);
    }

    public function test_modelo_professor_identifica_emissor_e_registra_hash_final(): void
    {
        $codigo = null;
        Process::fake(function ($p) use (&$codigo) {
            $d=json_decode($p->input,true);$this->arquivos[]=$d['saida'];
            $this->assertSame('professor',$d['modelo']);
            $this->assertSame('Professor fictício',$d['emissao']['emissor']);
            $this->assertSame(2,$d['folha'][0]['geral']['total']);
            $codigo=$d['emissao']['codigo'];
            file_put_contents($d['saida'],'%PDF-professor');
            return Process::result(output:'{"ok":true}');
        });
        $this->getJson('/api/v1/alunos/1/resultados/1/pdf?modelo=professor')->assertOk();
        $this->assertDatabaseCount('documento_emissoes',1);
        $this->assertDatabaseHas('documento_emissoes',['codigo_hash'=>hash('sha256',$codigo),'arquivo_sha256'=>hash('sha256','%PDF-professor'),'emissor_id'=>1]);
    }

    public function test_modelo_aluno_nao_carrega_agregados_para_o_gerador(): void
    {
        Process::fake(function ($p) {
            $d=json_decode($p->input,true);$this->arquivos[]=$d['saida'];
            $this->assertSame('aluno',$d['modelo']);
            $this->assertArrayNotHasKey('media_turma',$d);
            $this->assertArrayNotHasKey('turma',$d['folha'][0]);
            $this->assertArrayNotHasKey('geral',$d['folha'][0]);
            file_put_contents($d['saida'],'%PDF-aluno');
            return Process::result(output:'{"ok":true}');
        });
        $this->getJson('/api/v1/alunos/1/resultados/1/pdf?modelo=aluno')->assertOk();
        $this->getJson('/api/v1/alunos/1/resultados/1/pdf?modelo=externo')->assertUnprocessable();
    }

    public function test_historico_so_aparece_com_outra_leitura_e_referencia_anterior_nao_muda(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('public')->put('ficticio.jpg','foto ficticia');
        $h = app(\App\Services\Omr\HistoricoCartao::class);
        $c = \App\Models\Cartao::find(1); $u = Usuario::find(1)->noVinculo(1);
        $antes = $h->fingerprint($c);
        $t = $h->registrarAtual($c,$u,extra:['leituras_omr'=>[['numero_questao'=>1,'alternativa'=>'A']]]);
        $this->assertSame($antes,$h->fingerprint($c));
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertOk()->assertJsonPath('data.total_leituras',1)->assertJsonPath('data.historico_permitido',false);
        $outro = \App\Models\Cartao::forceCreate(['prova_id'=>1,'aluno_id'=>1,'espaco_id'=>1,'status'=>'lido']);
        $h->registrarAtual($outro,$u);
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertOk()->assertJsonPath('data.total_leituras',2)->assertJsonPath('data.historico_permitido',true);
        DB::table('gabarito_questoes')->where('gabarito_id',1)->update(['alternativa'=>'B']);
        $this->assertSame($antes,$h->fingerprint($c));
        $this->getJson('/api/v1/tentativas/'.$t->id)->assertOk()->assertJsonPath('data.linhas.0.gabarito','A')
            ->assertJsonPath('data.linhas.0.situacao','correta')->assertJsonPath('data.linhas.0.referencia_preservada',true);
    }
}
