<?php
namespace Tests\Feature;

use App\Models\{Usuario, Cartao};
use App\Services\Omr\CalculadoraNota;
use App\Support\{ConsultasPedagogicas, ContextoCorrecao, Permissao};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DadosPessoaisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo'=>true, 'espacos.pessoal'=>true, 'vinculos.obrigatorio'=>true]);
        Permissao::limparCache();
        foreach ([1,2] as $id) {
            Usuario::forceCreate(['id'=>$id, 'perfil'=>'professor', 'nome'=>'Pessoa fictícia', 'email'=>"t3b$id@example.test", 'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT), 'password'=>'ficticio', 'ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id, 'tipo'=>'professor', 'nome'=>'Cliente fictício', 'modalidade'=>'interno', 'situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id, 'cliente_id'=>$id, 'tipo'=>'pessoal', 'nome'=>"Pessoal $id", 'situacao'=>'ativo', 'usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
            DB::table('turmas')->insert(['id'=>$id,'escola_id'=>null,'espaco_id'=>$id,'espaco_tipo'=>'pessoal','nome'=>'Turma','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
        }
        Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
    }

    private function aluno(): int
    {
        return $this->postJson('/api/v1/alunos',['nome'=>'Aluno fictício','matricula'=>'P-1','turma_id'=>1,'avatar_id'=>3])
            ->assertCreated()->assertJsonPath('data.espaco_id',1)->json('data.id');
    }

    private function prova(): int
    {
        return $this->postJson('/api/v1/provas',['titulo'=>'Prova','disciplina'=>'Teste','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4,'turma_ids'=>[1]])
            ->assertCreated()->assertJsonPath('data.escola_id',null)->json('data.id');
    }

    public function test_cadastro_minimo_espaco_derivado_e_tentativas_cruzadas(): void
    {
        $id = $this->aluno();
        $this->assertDatabaseHas('alunos',['id'=>$id,'espaco_tipo'=>'pessoal','cpf'=>null,'data_nascimento'=>null,'nome_responsavel'=>null]);
        foreach (['cpf'=>'12345678901','data_nascimento'=>'2010-01-01','genero'=>'M','nome_responsavel'=>'Vedado','foto_path'=>'vedado.jpg'] as $campo=>$valor) {
            $this->putJson("/api/v1/alunos/$id",[$campo=>$valor])->assertUnprocessable();
        }
        $this->putJson("/api/v1/alunos/$id",['turma_id'=>2])->assertNotFound();
        $this->postJson('/api/v1/alunos',['nome'=>'Fora','matricula'=>'P-2','turma_id'=>2])->assertNotFound();
        $this->putJson("/api/v1/alunos/$id",['nome'=>'Atualizado','espaco_id'=>2,'espaco_tipo'=>'escola'])->assertOk()->assertJsonPath('data.espaco_id',1);
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonCount(1,'data.turmas');
        $this->getJson('/api/v1/turmas/2')->assertNotFound();
        foreach (['escolas','usuarios','nucleos'] as $rota) $this->getJson('/api/v1/'.$rota)->assertForbidden();
    }

    public function test_turma_prova_e_agregados_pessoais_sem_escola_ficticia(): void
    {
        $this->postJson('/api/v1/turmas',['nome'=>'Nova','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026,'espaco_id'=>2])
            ->assertCreated()->assertJsonPath('data.espaco_id',1)->assertJsonPath('data.escola_id',null);
        $this->postJson('/api/v1/turmas',['escola_id'=>1])->assertUnprocessable();
        $this->aluno(); $p = $this->prova();
        $this->getJson('/api/v1/provas')->assertOk()->assertJsonPath('data.provas.0.total_alunos',1);
        $this->putJson("/api/v1/provas/$p",['turma_ids'=>[2]])->assertNotFound();
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.total_alunos',1);
    }

    public function test_mudanca_de_turma_preserva_nota_cartao_recalculo_e_boletim(): void
    {
        $aluno = $this->aluno(); $p = $this->prova();
        DB::table('provas')->where('id',$p)->update(['status'=>'publicada']);
        $g = DB::table('gabaritos')->insertGetId(['prova_id'=>$p,'espaco_id'=>1]);
        DB::table('gabarito_questoes')->insert(['gabarito_id'=>$g,'espaco_id'=>1,'numero_questao'=>1,'alternativa'=>'A','peso'=>1]);
        $c = Cartao::forceCreate(['prova_id'=>$p,'aluno_id'=>$aluno,'espaco_id'=>1,'imagem_url'=>'cartoes/ficticio.jpg','status'=>'lido']);
        DB::table('cartao_respostas')->insert(['cartao_id'=>$c->id,'espaco_id'=>1,'numero_questao'=>1,'alternativa'=>'A']);
        CalculadoraNota::calcular($c,1);
        // Exercita também cartão anterior ao snapshot: a movimentação fixa a nota já existente.
        DB::table('cartoes')->where('id',$c->id)->update(['turma_aplicacao_id'=>null]);
        $destino = $this->postJson('/api/v1/turmas',['nome'=>'Destino','serie'=>'4','turno'=>'tarde','ano_letivo'=>2026])->assertCreated()->json('data.id');
        $this->putJson("/api/v1/alunos/$aluno",['turma_id'=>$destino])->assertOk();
        $this->assertDatabaseHas('alunos',['id'=>$aluno,'turma_id'=>$destino]);
        $this->assertDatabaseHas('cartoes',['id'=>$c->id,'turma_aplicacao_id'=>1]);
        $this->assertTrue(ContextoCorrecao::cartaoValido($c->fresh()));
        CalculadoraNota::calcular($c->fresh(),1);
        $this->assertDatabaseHas('notas',['cartao_id'=>$c->id,'turma_id'=>1,'nota_final'=>10]);
        $consulta = new ConsultasPedagogicas(Usuario::find(1)->noVinculo(1));
        $this->assertSame(1,$consulta->tabela('notas')->count());
        $this->getJson("/api/v1/relatorios/aluno/$aluno")->assertOk()->assertJsonPath('data.kpis.feitas',1)->assertJsonPath('data.provas.0.nota',10)->assertJsonPath('data.cabecalho.nome','Pessoal 1');
        $this->putJson("/api/v1/alunos/$aluno",['turma_id'=>1])->assertOk();
        $this->assertDatabaseHas('notas',['cartao_id'=>$c->id,'turma_id'=>1]);
    }

    public function test_banco_recusa_tipo_falso_cadastro_excessivo_e_escola_nula_institucional(): void
    {
        $id=$this->aluno();
        foreach ([
            fn()=>DB::table('alunos')->where('id',$id)->update(['nome_responsavel'=>'Vedado']),
            fn()=>DB::table('alunos')->where('id',$id)->update(['espaco_tipo'=>'escola']),
            fn()=>DB::table('turmas')->where('id',1)->update(['escola_id'=>1]),
            fn()=>DB::table('turmas')->where('id',1)->update(['espaco_tipo'=>'escola']),
        ] as $acao) {
            try { $acao(); $this->fail('O banco deveria impedir a escrita.'); }
            catch (\Illuminate\Database\QueryException $e) { $this->assertNotEmpty($e->getCode()); }
        }
        $this->assertDatabaseHas('alunos',['id'=>$id,'nome_responsavel'=>null,'espaco_tipo'=>'pessoal']);
    }

    public function test_historico_inconsistente_reverte_toda_movimentacao(): void
    {
        $aluno=$this->aluno(); $p=$this->prova();
        $primeiro=DB::table('cartoes')->insertGetId(['prova_id'=>$p,'aluno_id'=>$aluno,'espaco_id'=>1,'imagem_url'=>'ficticio.jpg']);
        $semTurma=$this->postJson('/api/v1/provas',['titulo'=>'Sem turma','disciplina'=>'Teste','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4])->assertCreated()->json('data.id');
        DB::table('cartoes')->insert(['prova_id'=>$semTurma,'aluno_id'=>$aluno,'espaco_id'=>1,'imagem_url'=>'ficticio2.jpg']);
        $destino=$this->postJson('/api/v1/turmas',['nome'=>'Destino','serie'=>'4','turno'=>'tarde','ano_letivo'=>2026])->assertCreated()->json('data.id');
        $this->putJson("/api/v1/alunos/$aluno",['turma_id'=>$destino,'nome'=>'Não salvar'])->assertConflict();
        $this->assertDatabaseHas('alunos',['id'=>$aluno,'turma_id'=>1,'nome'=>'Aluno fictício']);
        $this->assertDatabaseHas('cartoes',['id'=>$primeiro,'turma_aplicacao_id'=>null]);
    }
}
