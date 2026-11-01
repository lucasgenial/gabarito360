<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstituicoesEspacosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private array $espacos = [];
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo' => true]);
        Permissao::limparCache();
        Mail::fake();
        Storage::fake('public');
        $this->admin = $this->usuario('admin_rede');
        foreach ([1, 2] as $id) {
            DB::table('redes')->insert(['id' => $id, 'nome' => 'Rede '.$id, 'tipo' => 'estadual', 'uf' => 'BA', 'meta_minima' => $id]);
            DB::table('nucleos')->insert(['id' => $id, 'rede_id' => $id, 'nome' => 'Núcleo '.$id]);
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => $id, 'nome' => 'Escola '.$id, 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            $cliente = DB::table('clientes')->insertGetId(['nome' => 'Cliente '.$id, 'tipo' => 'rede', 'modalidade' => 'interno', 'situacao' => 'ativo']);
            foreach (['rede', 'nucleo', 'escola'] as $tipo) {
                $this->espacos[$id][$tipo] = DB::table('espacos')->insertGetId(['cliente_id' => $cliente, 'tipo' => $tipo, 'nome' => $tipo.' '.$id,
                    $tipo.'_id' => $id, 'situacao' => 'ativo']);
            }
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $this->espacos[$id]['escola'], 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        }
        $this->escopo($this->admin, 'rede', 1);
        Sanctum::actingAs($this->admin);
    }

    private function usuario(string $perfil = 'professor'): Usuario
    {
        $n = ++$this->seq;
        return Usuario::create(['nome' => 'Conta fictícia '.$n, 'perfil' => $perfil, 'email' => 'conta'.$n.'@example.test',
            'cpf' => str_pad((string) $n, 11, '0', STR_PAD_LEFT), 'password' => bcrypt('teste'), 'ativo' => true]);
    }

    private function escopo(Usuario $u, string $tipo, int $id): void
    {
        DB::table('usuario_escopos')->insert(['usuario_id' => $u->id, 'escopo_tipo' => $tipo, 'escopo_id' => $id]);
    }

    public function test_listas_detalhes_e_mutacoes_institucionais_ficam_na_rede(): void
    {
        $this->getJson('/api/v1/escolas')->assertOk()->assertJsonCount(1, 'data.escolas')->assertJsonPath('data.kpis.total_escolas', 1);
        $this->getJson('/api/v1/nucleos')->assertOk()->assertJsonCount(1, 'data.nucleos');
        foreach (['escolas', 'nucleos'] as $tipo) {
            $this->getJson('/api/v1/'.$tipo.'/2')->assertNotFound();
            $this->putJson('/api/v1/'.$tipo.'/2', ['nome' => 'Não salvar'])->assertNotFound();
        }
        $this->deleteJson('/api/v1/nucleos/2')->assertNotFound();
        $this->postJson('/api/v1/escolas/2/toggle')->assertNotFound();
        DB::table('espacos')->where('id', $this->espacos[1]['rede'])->update(['situacao' => 'suspenso']);
        $this->getJson('/api/v1/escolas/1')->assertNotFound();
        $this->getJson('/api/v1/nucleos/1')->assertNotFound();
    }

    public function test_configuracao_exige_selecao_quando_ha_duas_redes(): void
    {
        $this->getJson('/api/v1/configuracoes')->assertOk()->assertJsonPath('data.rede_id', 1);
        $this->getJson('/api/v1/configuracoes?rede_id=2')->assertNotFound();
        $this->escopo($this->admin, 'rede', 2);
        $this->getJson('/api/v1/configuracoes')->assertUnprocessable();
        $dados = ['meta_minima' => 7, 'meta_media' => 8, 'limiar_seges_min' => 30];
        $this->putJson('/api/v1/configuracoes', $dados)->assertUnprocessable();
        $this->putJson('/api/v1/configuracoes', $dados + ['rede_id' => 2])->assertOk();
        $this->assertDatabaseHas('redes', ['id' => 1, 'meta_minima' => 1]);
        $this->assertDatabaseHas('redes', ['id' => 2, 'meta_minima' => 7]);
    }

    public function test_criacao_de_instituicao_herda_cliente_em_transacao_sem_transferencia(): void
    {
        $n = $this->postJson('/api/v1/nucleos', ['nome' => 'Novo', 'rede_id' => 1, 'cliente_id' => 2])->assertCreated()->json('data.id');
        $e = $this->postJson('/api/v1/escolas', ['nome' => 'Nova', 'nucleo_id' => $n, 'inep' => '00000003', 'tipo_rede' => 'estadual', 'cliente_id' => 2])->assertCreated()->json('data.id');
        $cliente = DB::table('espacos')->where('id', $this->espacos[1]['rede'])->value('cliente_id');
        $this->assertDatabaseHas('espacos', ['nucleo_id' => $n, 'cliente_id' => $cliente, 'criado_por' => $this->admin->id]);
        $this->assertDatabaseHas('espacos', ['escola_id' => $e, 'cliente_id' => $cliente, 'criado_por' => $this->admin->id]);
        $this->escopo($this->admin, 'rede', 2);
        $this->putJson('/api/v1/nucleos/'.$n, ['rede_id' => 2])->assertUnprocessable();
        $this->putJson('/api/v1/escolas/'.$e, ['nucleo_id' => 2])->assertUnprocessable();
        $this->deleteJson('/api/v1/nucleos/'.$n)->assertUnprocessable();
    }

    public function test_logos_conferem_acesso_antes_de_abrir_arquivo(): void
    {
        foreach ([1, 2] as $id) {
            foreach (['escolas', 'redes'] as $tipo) {
                $path = $tipo.'/'.$id.'/logos/ficticio.png';
                Storage::disk('public')->put($path, 'imagem fictícia');
                DB::table($tipo)->where('id', $id)->update(['logo_path' => $path]);
                $r = $this->get('/api/v1/'.$tipo.'/'.$id.'/logo');
                $id === 1 ? $r->assertOk() : $r->assertNotFound();
            }
        }
        DB::table('espacos')->where('id', $this->espacos[1]['rede'])->update(['situacao' => 'suspenso']);
        $this->get('/api/v1/redes/1/logo')->assertNotFound();
        $this->get('/api/v1/escolas/1/logo')->assertNotFound();
        $this->assertCount(4, Storage::disk('public')->allFiles());
    }

    public function test_conta_com_escopo_alheio_nao_sofre_alteracao_global(): void
    {
        $local = $this->usuario(); $externo = $this->usuario(); $misto = $this->usuario();
        $this->escopo($local, 'escola', 1); $this->escopo($externo, 'escola', 2);
        $this->escopo($misto, 'escola', 1); $this->escopo($misto, 'escola', 2);
        $this->getJson('/api/v1/usuarios')->assertOk()->assertJsonCount(1, 'data.usuarios')->assertJsonPath('data.totais.professor', 1);
        foreach ([$externo, $misto] as $u) {
            $this->getJson('/api/v1/usuarios/'.$u->id)->assertNotFound();
            $this->putJson('/api/v1/usuarios/'.$u->id, ['ativo' => false])->assertNotFound();
            $this->postJson('/api/v1/usuarios/'.$u->id.'/toggle')->assertNotFound();
            $this->getJson('/api/v1/usuarios/'.$u->id.'/permissoes')->assertNotFound();
            $this->putJson('/api/v1/usuarios/'.$u->id.'/permissoes', ['itens' => [['permissao' => 'turma.ver', 'estado' => 'retirar']]])->assertNotFound();
            $this->postJson('/api/v1/usuarios/'.$u->id.'/link-senha')->assertNotFound();
            $this->assertTrue($u->fresh()->ativo);
        }
        Mail::assertNothingQueued();
        $this->putJson('/api/v1/usuarios/'.$local->id, ['nome' => 'Permitido'])->assertOk();
        $this->putJson('/api/v1/usuarios/'.$local->id, ['escola_id' => 2])->assertUnprocessable();
        $this->putJson('/api/v1/usuarios/'.$local->id, ['perfil' => 'admin_rede'])->assertUnprocessable();
        $this->escopo($this->admin, 'rede', 2);
        $this->getJson('/api/v1/usuarios')->assertOk()->assertJsonCount(3, 'data.usuarios');
    }

    public function test_professor_so_e_vinculado_a_turma_da_escola_dele(): void
    {
        $prof = $this->usuario(); $this->escopo($prof, 'escola', 2);
        $this->postJson('/api/v1/turmas/1/professores', ['usuario_id' => $prof->id])->assertNotFound();
        $this->getJson('/api/v1/turmas/2/professores')->assertNotFound();
        $this->getJson('/api/v1/usuarios/'.$prof->id.'/turmas')->assertNotFound();
        $this->escopo($prof, 'escola', 1);
        $this->postJson('/api/v1/turmas/1/professores', ['usuario_id' => $prof->id])->assertCreated();
        $this->assertDatabaseHas('usuario_escopos', ['usuario_id' => $prof->id, 'escopo_tipo' => 'turma', 'escopo_id' => 1]);
        $this->getJson('/api/v1/turmas/1/professores')->assertOk()->assertJsonCount(1, 'data.professores');
        Sanctum::actingAs($prof);
        Permissao::limparCache();
        $this->getJson('/api/v1/usuarios/'.$prof->id.'/turmas')->assertOk()->assertJsonCount(1, 'data.turmas');
    }

    public function test_relacoes_divergentes_nao_aparecem_nos_agregados_da_escola(): void
    {
        DB::table('alunos')->insert(['turma_id' => 1, 'espaco_id' => $this->espacos[2]['escola'], 'nome' => 'Fora', 'matricula' => 'FORA']);
        $this->getJson('/api/v1/escolas')->assertOk()->assertJsonPath('data.kpis.total_alunos', 0)->assertJsonPath('data.escolas.0.total_alunos', 0);
        $this->getJson('/api/v1/escolas/1')->assertOk()->assertJsonCount(0, 'data.alunos')->assertJsonPath('data.total_alunos', 0);
    }

    public function test_totais_de_usuarios_no_regime_legado_tambem_respeitam_escopo(): void
    {
        config(['espacos.ativo' => false]);
        $this->admin->perfil = 'dir_escolar';
        $this->escopo($this->admin, 'escola', 1);
        $local = $this->usuario(); $fora = $this->usuario();
        $this->escopo($local, 'escola', 1); $this->escopo($fora, 'escola', 2);
        Permissao::limparCache();
        $this->getJson('/api/v1/usuarios')->assertOk()->assertJsonCount(1, 'data.usuarios')->assertJsonPath('data.totais.professor', 1);
    }

    public function test_aluno_acessa_apenas_logo_da_propria_escola_sem_receber_acesso_a_turmas(): void
    {
        $u = $this->usuario('aluno');
        $aluno = DB::table('alunos')->insertGetId(['turma_id' => 1, 'espaco_id' => $this->espacos[1]['escola'], 'nome' => 'Aluno fictício', 'matricula' => 'LOGO']);
        $this->escopo($u, 'aluno', $aluno);
        Storage::disk('public')->put('escolas/1/logo.png', 'imagem fictícia');
        DB::table('escolas')->where('id', 1)->update(['logo_path' => 'escolas/1/logo.png']);
        Sanctum::actingAs($u);
        Permissao::limparCache();
        $this->get('/api/v1/escolas/1/logo')->assertOk();
        $this->get('/api/v1/escolas/2/logo')->assertNotFound();
        $this->assertSame([], \App\Support\Escopo::turmaIds($u));
    }

    public function test_cadastro_nao_injeta_escola_alheia_em_perfil_de_nucleo(): void
    {
        $dados = ['nome' => 'Diretor fictício', 'email' => 'novo@example.test', 'cpf' => '99999999999',
            'perfil' => 'dir_nucleo', 'nucleo_id' => 1, 'escola_id' => 2];
        $id = $this->postJson('/api/v1/usuarios', $dados)->assertCreated()->json('data.id');
        $this->assertDatabaseHas('usuario_escopos', ['usuario_id' => $id, 'escopo_tipo' => 'nucleo', 'escopo_id' => 1]);
        $this->assertDatabaseMissing('usuario_escopos', ['usuario_id' => $id, 'escopo_tipo' => 'escola']);
        $this->postJson('/api/v1/usuarios', array_replace($dados, ['perfil' => 'admin_rede', 'email' => 'adminnovo@example.test', 'cpf' => '88888888888']))->assertUnprocessable();
        $this->assertDatabaseCount('usuarios', 2);
    }

    public function test_professor_nao_amplia_turmas_pelo_detalhe_da_escola(): void
    {
        $prof = $this->usuario(); $this->escopo($prof, 'escola', 1); $this->escopo($prof, 'turma', 1);
        DB::table('turmas')->insert(['id' => 3, 'escola_id' => 1, 'espaco_id' => $this->espacos[1]['escola'], 'nome' => 'Outra', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        DB::table('alunos')->insert(['turma_id' => 3, 'espaco_id' => $this->espacos[1]['escola'], 'nome' => 'Não listar', 'matricula' => 'OUTRA']);
        Sanctum::actingAs($prof); Permissao::limparCache();
        $this->getJson('/api/v1/escolas/1')->assertOk()->assertJsonCount(1, 'data.turmas')->assertJsonCount(0, 'data.alunos');
        $this->getJson('/api/v1/escolas')->assertOk()->assertJsonPath('data.kpis.total_turmas', 1)->assertJsonPath('data.kpis.total_alunos', 0);
    }
}
