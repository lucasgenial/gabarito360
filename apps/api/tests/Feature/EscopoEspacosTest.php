<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Escopo;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EscopoEspacosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private array $es = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo' => true]);
        Permissao::limparCache();
        $this->admin = Usuario::create(['nome' => 'Gestor fictício', 'perfil' => 'admin_rede',
            'email' => 't2d@example.test', 'cpf' => '00000000001', 'password' => bcrypt('teste'), 'ativo' => true]);
        // Mesmo pagador não dá acesso à outra rede.
        $cliente = DB::table('clientes')->insertGetId(['tipo' => 'rede', 'nome' => 'Interno fictício', 'modalidade' => 'interno', 'situacao' => 'ativo']);
        foreach ([1, 2] as $id) {
            DB::table('redes')->insert(['id' => $id, 'nome' => 'Rede '.$id, 'tipo' => 'estadual', 'uf' => 'BA']);
            DB::table('nucleos')->insert(['id' => $id, 'rede_id' => $id, 'nome' => 'Núcleo '.$id]);
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => $id, 'nome' => 'Escola '.$id, 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            foreach (['rede', 'nucleo', 'escola'] as $tipo) {
                $this->es[$id][$tipo] = DB::table('espacos')->insertGetId(['cliente_id' => $cliente, 'tipo' => $tipo,
                    'nome' => $tipo.' '.$id, 'situacao' => 'ativo', $tipo.'_id' => $id]);
            }
            $espaco = $this->es[$id]['escola'];
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $espaco, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            DB::table('alunos')->insert(['id' => $id, 'turma_id' => $id, 'espaco_id' => $espaco, 'nome' => 'Aluno '.$id, 'matricula' => 'T2D-'.$id]);
            DB::table('provas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $espaco, 'criado_por' => $this->admin->id,
                'titulo' => 'Prova '.$id, 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 4]);
        }
        $this->atribuir(1);
        Sanctum::actingAs($this->admin);
    }

    private function atribuir(int $rede): void
    {
        DB::table('usuario_escopos')->insert(['usuario_id' => $this->admin->id, 'escopo_tipo' => 'rede', 'escopo_id' => $rede]);
    }

    public function test_admin_so_recebe_redes_explicitamente_atribuidas(): void
    {
        $this->assertSame([1], Escopo::escolaIds($this->admin));
        $this->getJson('/api/v1/alunos')->assertOk()->assertJsonCount(1, 'data.alunos')->assertJsonPath('data.kpis.total_alunos', 1);
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonCount(1, 'data.turmas');
        $this->getJson('/api/v1/provas')->assertOk()->assertJsonCount(1, 'data.provas');
        foreach (['alunos', 'turmas', 'provas'] as $tipo) {
            $this->getJson('/api/v1/'.$tipo.'/2')->assertNotFound();
            $this->putJson('/api/v1/'.$tipo.'/2', ['nome' => 'Não salvar', 'titulo' => 'Não salvar'])->assertNotFound();
        }
        foreach (['alunos', 'turmas'] as $tipo) $this->postJson('/api/v1/'.$tipo.'/2/toggle')->assertNotFound();
        $this->atribuir(2);
        $this->assertSame([1, 2], Escopo::escolaIds($this->admin));
        DB::table('usuario_escopos')->where('usuario_id', $this->admin->id)->delete();
        $this->assertSame([], Escopo::escolaIds($this->admin));
        $this->getJson('/api/v1/alunos')->assertOk()->assertJsonCount(0, 'data.alunos');
    }

    public function test_suspensao_de_cada_nivel_revoga_acesso_sem_cache_residual(): void
    {
        foreach (['escola', 'nucleo', 'rede'] as $tipo) {
            DB::table('espacos')->where('id', $this->es[1][$tipo])->update(['situacao' => 'suspenso']);
            $this->getJson('/api/v1/provas/1')->assertNotFound();
            $this->assertSame([], Escopo::turmaIds($this->admin));
            DB::table('espacos')->where('id', $this->es[1][$tipo])->update(['situacao' => 'ativo']);
            $this->getJson('/api/v1/provas/1')->assertOk();
        }
        $this->admin->ativo = false;
        $this->assertSame([], Escopo::escolaIds($this->admin));
    }

    public function test_espaco_nulo_ou_de_outra_rede_nao_reabre_acesso(): void
    {
        DB::table('alunos')->where('id', 1)->update(['espaco_id' => null]);
        DB::table('turmas')->where('id', 1)->update(['espaco_id' => $this->es[2]['escola']]);
        DB::table('provas')->where('id', 1)->update(['espaco_id' => $this->es[2]['escola']]);
        foreach (['alunos', 'turmas', 'provas'] as $tipo) $this->getJson('/api/v1/'.$tipo.'/1')->assertNotFound();
    }

    public function test_escritas_calculam_espaco_e_autoria_no_servidor(): void
    {
        $turma = $this->postJson('/api/v1/turmas', ['escola_id' => 1, 'nome' => 'Nova', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026,
            'espaco_id' => $this->es[2]['escola'], 'criado_por' => 999])->assertCreated()->json('data.id');
        $aluno = $this->postJson('/api/v1/alunos', ['turma_id' => $turma, 'nome' => 'Fictício', 'matricula' => 'NOVO', 'data_nascimento' => '2010-01-01',
            'espaco_id' => $this->es[2]['escola']])->assertCreated()->json('data.id');
        $prova = $this->postJson('/api/v1/provas', ['escola_id' => 1, 'titulo' => 'Nova', 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 1,
            'num_alternativas' => 4, 'turma_ids' => [$turma], 'espaco_id' => $this->es[2]['escola']])->assertCreated()->json('data.id');
        foreach (['turmas' => $turma, 'alunos' => $aluno, 'provas' => $prova] as $t => $id) {
            $this->assertDatabaseHas($t, ['id' => $id, 'espaco_id' => $this->es[1]['escola'], 'criado_por' => $this->admin->id, 'origem_autoria' => 'registrada']);
        }
        $this->assertDatabaseHas('prova_turmas', ['prova_id' => $prova, 'turma_id' => $turma, 'espaco_id' => $this->es[1]['escola']]);
        DB::table('prova_turmas')->where('prova_id', $prova)->update(['criado_por' => null, 'origem_autoria' => 'legado_desconhecido']);
        $this->putJson('/api/v1/provas/'.$prova, ['titulo' => 'Editada', 'turma_ids' => [$turma]])->assertOk();
        $this->assertDatabaseHas('prova_turmas', ['prova_id' => $prova, 'turma_id' => $turma, 'criado_por' => null, 'origem_autoria' => 'legado_desconhecido']);
        $this->putJson('/api/v1/provas/'.$prova, ['turma_ids' => [$turma, $turma]])->assertUnprocessable();
    }

    public function test_mesmo_gestor_de_duas_redes_nao_transfere_propriedade_por_edicao(): void
    {
        $this->atribuir(2);
        $this->putJson('/api/v1/turmas/1', ['escola_id' => 2])->assertUnprocessable();
        $this->putJson('/api/v1/alunos/1', ['turma_id' => 2])->assertUnprocessable();
        $this->putJson('/api/v1/provas/1', ['turma_ids' => [2]])->assertNotFound();
        $this->assertDatabaseHas('turmas', ['id' => 1, 'escola_id' => 1]);
        $this->assertDatabaseHas('alunos', ['id' => 1, 'turma_id' => 1]);
    }

    public function test_escola_e_turma_alheias_nao_podem_ser_usadas_para_cadastro(): void
    {
        $this->postJson('/api/v1/turmas', ['escola_id' => 2, 'nome' => 'Nova', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026])->assertNotFound();
        $this->postJson('/api/v1/alunos', ['turma_id' => 2, 'nome' => 'Novo', 'matricula' => 'NOVA', 'data_nascimento' => '2010-01-01'])->assertNotFound();
        $this->assertDatabaseCount('turmas', 2);
        $this->assertDatabaseCount('alunos', 2);
    }

    public function test_professor_continua_limitado_as_turmas_e_provas_atribuidas(): void
    {
        $this->admin->perfil = 'professor';
        DB::table('usuario_escopos')->where('usuario_id', $this->admin->id)->delete();
        DB::table('usuario_escopos')->insert(['usuario_id' => $this->admin->id, 'escopo_tipo' => 'turma', 'escopo_id' => 1]);
        $this->assertSame([1], Escopo::turmaIds($this->admin));
        $this->assertSame([1], Escopo::provas($this->admin)->pluck('id')->all());
        DB::table('espacos')->where('id', $this->es[1]['rede'])->update(['situacao' => 'suspenso']);
        $this->assertSame([], Escopo::turmaIds($this->admin));
        $this->assertSame([], Escopo::provas($this->admin)->pluck('id')->all());
    }

    public function test_relacoes_divergentes_nao_vazam_contagens_ou_detalhes(): void
    {
        DB::table('alunos')->where('id', 2)->update(['turma_id' => 1]);
        DB::table('prova_turmas')->insert(['prova_id' => 1, 'turma_id' => 2, 'espaco_id' => $this->es[2]['escola']]);
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonCount(1, 'data.alunos');
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonPath('data.kpis.total_alunos', 1);
        $this->getJson('/api/v1/provas/1')->assertOk()->assertJsonCount(0, 'data.turmas');
        $this->getJson('/api/v1/provas')->assertOk()->assertJsonPath('data.provas.0.total_alunos', 0);
        // Mesmo com acesso às duas redes, propriedade divergente é inválida.
        $this->atribuir(2);
        $this->getJson('/api/v1/alunos/2')->assertNotFound();
        DB::table('turmas')->where('id', 1)->update(['espaco_id' => $this->es[2]['escola']]);
        $this->getJson('/api/v1/turmas/1')->assertNotFound();
    }

    public function test_correcao_de_escritas_no_escopo_legado_independe_da_ativacao_t2(): void
    {
        config(['espacos.ativo' => false]);
        $this->admin->perfil = 'dir_escolar';
        DB::table('usuario_escopos')->where('usuario_id', $this->admin->id)->delete();
        DB::table('usuario_escopos')->insert(['usuario_id' => $this->admin->id, 'escopo_tipo' => 'escola', 'escopo_id' => 1]);
        Permissao::limparCache();
        foreach (['alunos', 'turmas'] as $tipo) {
            $this->putJson('/api/v1/'.$tipo.'/2', ['nome' => 'Não salvar'])->assertNotFound();
            $this->postJson('/api/v1/'.$tipo.'/2/toggle')->assertNotFound();
        }
        $this->putJson('/api/v1/alunos/1', ['nome' => 'Permitido'])->assertOk();
    }
}
