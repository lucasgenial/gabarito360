<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Jobs\ProcessarCartaoJob;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsultasEspacosTest extends TestCase
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
        Queue::fake([ProcessarCartaoJob::class]);
        foreach ([1, 2] as $id) {
            $meta = ['espaco_id' => $this->espacos[$id]['escola'], 'criado_por' => $this->admin->id, 'origem_autoria' => 'registrada'];
            DB::table('alunos')->insert(['id' => $id, 'turma_id' => $id, 'nome' => 'Aluno '.$id, 'matricula' => 'FICTICIO-'.$id, 'ativo' => true] + $meta);
            DB::table('provas')->insert(['id' => $id, 'escola_id' => $id, 'titulo' => 'Prova '.$id, 'disciplina' => 'Matemática', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 5, 'status' => 'publicada', 'data_aplicacao' => now()->toDateString(), 'nota_maxima' => 10] + $meta);
            DB::table('prova_turmas')->insert(['prova_id' => $id, 'turma_id' => $id] + $meta);
            DB::table('gabaritos')->insert(['id' => $id, 'prova_id' => $id] + $meta);
            DB::table('gabarito_questoes')->insert(['gabarito_id' => $id, 'numero_questao' => 1, 'alternativa' => 'A'] + $meta);
            DB::table('cartoes')->insert(['id' => $id, 'prova_id' => $id, 'aluno_id' => $id, 'status' => 'lido'] + $meta);
            DB::table('cartao_respostas')->insert(['cartao_id' => $id, 'numero_questao' => 1, 'alternativa' => 'A', 'confianca' => 1, 'ambigua' => false] + $meta);
            DB::table('notas')->insert(['cartao_id' => $id, 'prova_id' => $id, 'aluno_id' => $id, 'turma_id' => $id, 'acertos' => 1, 'total_questoes' => 1, 'nota_final' => $id === 1 ? 8 : 2, 'status_aprovacao' => $id === 1 ? 'aprovado' : 'recuperacao'] + $meta);
        }
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



    public function test_admin_painel_e_consolidados_somente_descendentes_autorizados(): void
    {
        $this->getJson('/api/v1/dashboard/admin')->assertOk()->assertJsonPath('data.kpis.total_escolas', 1)->assertJsonPath('data.kpis.total_alunos', 1)->assertJsonPath('data.kpis.media_rede', 8);
        foreach (['rede', 'nucleo', 'escola'] as $nivel) {
            $this->getJson('/api/v1/relatorios/'.$nivel.'/1')->assertOk()->assertJsonPath('data.kpis.notas', 1)->assertJsonPath('data.kpis.media', 8);
            $this->getJson('/api/v1/relatorios/'.$nivel.'/2')->assertNotFound();
        }
        $this->getJson('/api/v1/relatorios/rede')->assertOk()->assertJsonPath('data.unidade.id', 1);
        $this->escopo($this->admin, 'rede', 2);
        $this->getJson('/api/v1/relatorios/rede')->assertOk()->assertJsonCount(2, 'data.escolher');
        $this->getJson('/api/v1/relatorios/rede/2')->assertOk()->assertJsonPath('data.kpis.media', 2);
        $this->getJson('/api/v1/relatorios/escola')->assertUnprocessable();
    }

    public function test_relatorios_e_acompanhamento_rejeitam_ids_e_relacoes_cruzadas(): void
    {
        foreach (['relatorios/prova/1', 'relatorios/turma/1', 'relatorios/aluno/1', 'relatorios/turma/1/prova/1', 'turmas/1/correcao'] as $uri) $this->getJson('/api/v1/'.$uri)->assertOk();
        foreach (['relatorios/prova/2', 'relatorios/turma/2', 'relatorios/aluno/2', 'relatorios/turma/2/prova/1', 'turmas/2/correcao'] as $uri) $this->getJson('/api/v1/'.$uri)->assertNotFound();
        DB::table('notas')->where('aluno_id', 1)->update(['espaco_id' => $this->espacos[2]['escola']]);
        $this->getJson('/api/v1/relatorios/prova/1')->assertOk()->assertJsonPath('data.kpis.total_alunos', 0)->assertJsonCount(0, 'data.alunos');
        $this->getJson('/api/v1/turmas/1/correcao')->assertOk()->assertJsonPath('data.alunos.0.celulas.1.nota_final', null);
        DB::table('cartao_respostas')->where('cartao_id', 1)->update(['espaco_id' => $this->espacos[2]['escola']]);
        $this->getJson('/api/v1/relatorios/prova/1')->assertOk()->assertJsonPath('data.questoes.0.total', 0);
    }

    public function test_suspensao_revoga_painel_e_consolidado_sem_total_global(): void
    {
        DB::table('espacos')->where('id', $this->espacos[1]['rede'])->update(['situacao' => 'suspenso']);
        $this->getJson('/api/v1/dashboard/admin')->assertOk()->assertJsonPath('data.kpis.total_escolas', 0)->assertJsonCount(0, 'data.top5_escolas');
        $this->getJson('/api/v1/relatorios/rede/1')->assertNotFound();
    }

    public function test_paineis_escolares_e_nucleo_usam_contexto_e_recusam_selecao_alheia(): void
    {
        foreach (['dir_escolar' => 'dir-escolar', 'coordenador' => 'coordenador', 'aplicador' => 'aplicador', 'dir_nucleo' => 'dir-nucleo'] as $perfil => $rota) {
            $u = $this->usuario($perfil);
            $tipo = $perfil === 'dir_nucleo' ? 'nucleo' : 'escola';
            $this->escopo($u, $tipo, 1); Sanctum::actingAs($u);
            $this->getJson('/api/v1/dashboard/'.$rota)->assertOk();
            $this->getJson('/api/v1/dashboard/'.$rota.'?'.$tipo.'_id=2')->assertNotFound();
            $this->escopo($u, $tipo, 2);
            $this->getJson('/api/v1/dashboard/'.$rota)->assertUnprocessable();
            $this->getJson('/api/v1/dashboard/'.$rota.'?'.$tipo.'_id=2')->assertOk();
        }
    }

    public function test_professor_e_aluno_nao_recebem_colegas_fora_do_escopo(): void
    {
        $p = $this->usuario('professor'); $this->escopo($p, 'escola', 1); $this->escopo($p, 'turma', 1);
        Sanctum::actingAs($p);
        $this->getJson('/api/v1/dashboard/professor')->assertOk()->assertJsonCount(1, 'data.ranking_top5');
        $this->getJson('/api/v1/relatorios/prova/2')->assertNotFound();
        $a = $this->usuario('aluno'); $this->escopo($a, 'aluno', 1); Sanctum::actingAs($a);
        $this->getJson('/api/v1/dashboard/aluno')->assertOk()->assertJsonPath('data.kpis.minha_media', 8)->assertJsonCount(1, 'data.notas');
        $this->getJson('/api/v1/dashboard/aluno?aluno_id=2')->assertNotFound();
        DB::table('espacos')->where('id', $this->espacos[1]['escola'])->update(['situacao' => 'suspenso']);
        $this->getJson('/api/v1/dashboard/aluno')->assertNotFound();
    }

    public function test_importacao_csv_simula_preserva_autoria_e_recusa_transferencia(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('alunos.csv', "CLASS;NAME;MATRICULA\nNova;Fictício;NOVO-1\n");
        $args = ['escola_id' => 1, 'arquivo' => Storage::disk('local')->path('alunos.csv'), '--usuario' => $this->admin->id, '--espaco' => $this->espacos[1]['escola'], '--ano' => 2026];
        $this->artisan('g360:importar-alunos', $args)->assertSuccessful();
        $this->assertDatabaseCount('alunos', 2);
        $this->artisan('g360:importar-alunos', $args + ['--aplicar' => true])->assertSuccessful();
        $this->artisan('g360:importar-alunos', $args + ['--aplicar' => true])->assertSuccessful();
        $this->assertDatabaseCount('alunos', 3);
        $this->assertDatabaseHas('alunos', ['matricula' => 'NOVO-1', 'espaco_id' => $args['--espaco'], 'criado_por' => $this->admin->id]);
        Storage::disk('local')->put('alunos.csv', "CLASS;NAME;MATRICULA\nNova;Outro;FICTICIO-2\n");
        $this->artisan('g360:importar-alunos', $args + ['--aplicar' => true])->assertFailed();
        $this->assertDatabaseHas('alunos', ['id' => 2, 'turma_id' => 2]);
        $this->artisan('g360:importar-alunos', array_replace($args, ['--espaco' => $this->espacos[2]['escola']]))->assertFailed();
    }

    public function test_importacao_provas_simula_e_grava_filhos_no_destino(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('provas.json', json_encode(['titulo' => 'Importada', 'serie' => '3', 'provas' => [['disciplina' => 'Ciências', 'gabarito' => 'AB']]]));
        $args = ['arquivo' => Storage::disk('local')->path('provas.json'), '--escola' => 1, '--usuario' => $this->admin->id, '--espaco' => $this->espacos[1]['escola'], '--turmas' => '3A', '--ano' => 2026];
        $this->artisan('g360:importar-provas', $args)->assertSuccessful();
        $this->assertDatabaseCount('provas', 2);
        $this->artisan('g360:importar-provas', $args + ['--aplicar' => true])->assertSuccessful();
        $p = DB::table('provas')->where('titulo', 'Importada')->first();
        $this->assertEquals($args['--espaco'], $p->espaco_id);
        $this->assertDatabaseHas('prova_turmas', ['prova_id' => $p->id, 'turma_id' => 1, 'espaco_id' => $p->espaco_id]);
        $g = DB::table('gabaritos')->where('prova_id', $p->id)->first();
        $this->assertDatabaseHas('gabarito_questoes', ['gabarito_id' => $g->id, 'espaco_id' => $p->espaco_id, 'criado_por' => $this->admin->id]);
        $this->artisan('g360:importar-provas', array_replace($args, ['--escola' => 2]))->assertFailed();
    }

    public function test_sincronizacao_sem_destino_e_visita_divergente_nao_vazam_no_painel(): void
    {
        foreach ([null, $this->espacos[2]['escola'], $this->espacos[1]['escola']] as $espaco) DB::table('sincronizacoes_seges')->insert(['espaco_id' => $espaco, 'status' => 'sucesso', 'iniciado_em' => now(), 'duracao_minutos' => $espaco === $this->espacos[1]['escola'] ? 5 : 99]);
        $this->getJson('/api/v1/dashboard/admin')->assertOk()->assertJsonPath('data.alertas.seges_atraso_min', 5);
        $u = $this->usuario('dir_nucleo'); $this->escopo($u, 'nucleo', 1); Sanctum::actingAs($u);
        DB::table('visitas')->insert(['nucleo_id' => 1, 'escola_id' => 2, 'espaco_id' => $this->espacos[1]['escola'], 'agendado_por' => $u->id, 'data_visita' => now()->toDateString(), 'tipo' => 'Fictícia', 'urgencia' => 'monitorar']);
        $this->getJson('/api/v1/dashboard/dir-nucleo')->assertOk()->assertJsonCount(0, 'data.visitas');
    }

    public function test_secretaria_requer_rede_explicitamente_atribuida(): void
    {
        $id = DB::table('secretarias')->insertGetId(['nome' => 'Secretaria fictícia', 'tipo' => 'estadual']);
        DB::table('redes')->update(['secretaria_id' => $id]);
        $u = $this->usuario('secretario_educacao'); $this->escopo($u, 'secretaria', $id); Sanctum::actingAs($u);
        $this->getJson('/api/v1/dashboard/secretaria')->assertOk()->assertJsonPath('data.kpis.total_redes', 0);
        $this->escopo($u, 'rede', 1);
        $this->getJson('/api/v1/dashboard/secretaria')->assertOk()->assertJsonPath('data.kpis.total_redes', 1)->assertJsonPath('data.kpis.total_alunos', 1)->assertJsonPath('data.kpis.media_secretaria', 8);
    }

    public function test_censo_global_nao_atualiza_escola_sem_destino_explicito(): void
    {
        Storage::fake('local');
        $colunas = array_values(\App\Support\Censo::COLUNAS);
        $linha = array_fill_keys($colunas, '');
        $linha = array_replace($linha, ['NU_ANO_CENSO' => '2026', 'CO_ENTIDADE' => '00000001', 'NO_ENTIDADE' => 'Fictícia', 'SG_UF' => 'BA', 'CO_UF' => '29', 'CO_MUNICIPIO' => '2900000', 'NO_MUNICIPIO' => 'Nova Cidade', 'TP_DEPENDENCIA' => '2']);
        Storage::disk('local')->put('censo.csv', implode(';', $colunas)."\n".implode(';', $linha)."\n");
        DB::table('escolas')->where('id', 1)->update(['origem' => 'censo', 'edicao_manual' => false, 'cidade' => 'Anterior']);
        $args = ['arquivo' => Storage::disk('local')->path('censo.csv')];
        $this->artisan('g360:censo-importar', $args)->assertSuccessful();
        $this->assertDatabaseHas('escolas', ['id' => 1, 'cidade' => 'Anterior']);
        $this->artisan('g360:censo-importar', $args + ['--atualizar-escolas' => true, '--usuario' => $this->admin->id, '--espaco' => $this->espacos[2]['escola']])->assertFailed();
        $this->artisan('g360:censo-importar', $args + ['--atualizar-escolas' => true, '--usuario' => $this->admin->id, '--espaco' => $this->espacos[1]['escola']])->assertSuccessful();
        $this->assertDatabaseHas('escolas', ['id' => 1, 'cidade' => 'Nova Cidade']);
    }

    public function test_professor_na_prova_compartilhada_nao_ve_outra_turma_nem_seus_agregados(): void
    {
        $meta = ['espaco_id' => $this->espacos[1]['escola']];
        DB::table('turmas')->insert(['id' => 3, 'escola_id' => 1, 'nome' => 'Turma alheia', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026] + $meta);
        DB::table('alunos')->insert(['id' => 3, 'turma_id' => 3, 'nome' => 'Aluno alheio', 'matricula' => 'FICTICIO-3', 'ativo' => true] + $meta);
        DB::table('prova_turmas')->insert(['prova_id' => 1, 'turma_id' => 3] + $meta);
        DB::table('cartoes')->insert(['id' => 3, 'prova_id' => 1, 'aluno_id' => 3, 'status' => 'lido'] + $meta);
        DB::table('notas')->insert(['cartao_id' => 3, 'prova_id' => 1, 'aluno_id' => 3, 'turma_id' => 3, 'acertos' => 0, 'total_questoes' => 1, 'nota_final' => 0, 'status_aprovacao' => 'recuperacao'] + $meta);
        $p = $this->usuario('professor'); $this->escopo($p, 'escola', 1); $this->escopo($p, 'turma', 1); Sanctum::actingAs($p);
        $this->getJson('/api/v1/relatorios/prova/1')->assertOk()->assertJsonCount(1, 'data.alunos')->assertJsonPath('data.kpis.media', 8);
        $this->getJson('/api/v1/provas')->assertOk()->assertDontSee('Turma alheia')->assertJsonPath('data.provas.0.total_alunos', 1);
        $this->getJson('/api/v1/dashboard/professor')->assertOk()->assertJsonPath('data.provas.0.total_alunos', 1);
        $this->getJson('/api/v1/relatorios/turma/1')->assertOk()->assertJsonCount(1, 'data.provas.0.por_turma');
    }
}
