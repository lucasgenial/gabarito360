<?php

namespace Tests\Feature;

use App\Jobs\ProcessarCartaoJob;
use App\Models\Cartao;
use App\Models\CartaoEnvio;
use App\Models\Prova;
use App\Models\Usuario;
use App\Services\Omr\ImagensCartao;
use App\Services\Omr\OmrDriverInterface;
use App\Services\Omr\OmrIdentificacaoInterface;
use App\Support\Permissao;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class CartoesFilaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Prova $prova;

    private int $turma;

    private int $aluno;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake([ProcessarCartaoJob::class]);
        Storage::fake('public');
        Permissao::limparCache();
        $this->usuario = $this->usuario();
        Sanctum::actingAs($this->usuario);
        $rede = DB::table('redes')->insertGetId(['nome' => 'Rede fictícia', 'tipo' => 'estadual', 'uf' => 'BA']);
        $nucleo = DB::table('nucleos')->insertGetId(['rede_id' => $rede, 'nome' => 'Núcleo de teste']);
        $escola = DB::table('escolas')->insertGetId(['nucleo_id' => $nucleo, 'nome' => 'Escola fictícia', 'inep' => '29000001', 'tipo_rede' => 'estadual']);
        $this->turma = DB::table('turmas')->insertGetId(['escola_id' => $escola, 'nome' => '3A', 'serie' => '3ª série', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        $this->aluno = DB::table('alunos')->insertGetId(['turma_id' => $this->turma, 'nome' => 'Aluno fictício', 'matricula' => 'FICTICIO-1', 'ativo' => true]);
        $this->prova = $this->novaProva($escola);
        $this->mock(ImagensCartao::class, function ($m) {
            $m->shouldReceive('gerar')->andReturn(true);
            $m->shouldReceive('apagar')->andReturnNull();
        });
    }

    private function usuario(string $perfil = 'admin_rede'): Usuario
    {
        $n = Usuario::count() + 1;

        return Usuario::create(['perfil' => $perfil, 'nome' => 'Usuário fictício', 'email' => "fila{$n}@example.test",
            'cpf' => str_pad((string) $n, 11, '0', STR_PAD_LEFT), 'password' => 'senha-ficticia', 'ativo' => true]);
    }

    private function novaProva(int $escola): Prova
    {
        $p = Prova::create(['escola_id' => $escola, 'criado_por' => $this->usuario->id, 'titulo' => 'Prova fictícia',
            'disciplina' => 'Matemática', 'serie' => '3ª série', 'num_questoes' => 2, 'num_alternativas' => 5,
            'status' => 'publicada', 'data_aplicacao' => now()->toDateString(), 'nota_maxima' => 10]);
        DB::table('prova_turmas')->insert(['prova_id' => $p->id, 'turma_id' => $this->turma]);
        $g = DB::table('gabaritos')->insertGetId(['prova_id' => $p->id]);
        foreach ([1, 2] as $n) {
            DB::table('gabarito_questoes')->insert(['gabarito_id' => $g, 'numero_questao' => $n, 'alternativa' => 'A']);
        }

        return $p;
    }

    private function enviar(?string $chave = null, ?int $aluno = null): CartaoEnvio
    {
        $chave ??= (string) Str::uuid();
        $this->postJson('/api/v1/provas/'.$this->prova->id.'/cartoes', ['envio_id' => $chave,
            'imagem' => UploadedFile::fake()->image('cartao.jpg'), 'aluno_id' => $aluno])
            ->assertStatus(202)->assertJsonPath('data.envio_id', $chave)->assertJsonPath('data.processamento', 'na_fila');

        return CartaoEnvio::where('chave', $chave)->firstOrFail();
    }

    private function job(CartaoEnvio $e): ProcessarCartaoJob
    {
        return new ProcessarCartaoJob($e->cartao_id, $e->id, $e->tentativa);
    }

    private function uri(CartaoEnvio $e): string
    {
        return "/api/v1/provas/{$e->prova_id}/cartoes/envios/{$e->chave}";
    }

    private function driver(array $id = [], bool $ambigua = false): OmrDriverInterface
    {
        $d = Mockery::mock(OmrDriverInterface::class.','.OmrIdentificacaoInterface::class);
        $d->shouldReceive('processar')->andReturn(array_map(fn ($n) => ['numero_questao' => $n, 'alternativa' => 'A',
            'confianca' => $ambigua ? 0.5 : 1, 'ambigua' => $ambigua, 'multiplas_marcacoes' => false, 'alternativas_detectadas' => []], [1, 2]));
        $d->shouldReceive('identificacao')->andReturn($id);

        return $d;
    }

    public function test_envio_responde_antes_do_omr_e_repeticao_nao_duplica(): void
    {
        $this->mock(OmrDriverInterface::class)->shouldNotReceive('processar');
        $e = $this->enviar(aluno: $this->aluno);
        $this->enviar($e->chave, $this->aluno);
        $this->assertDatabaseCount('cartoes', 1);
        $this->assertDatabaseCount('cartao_envios', 1);
        Queue::assertPushed(ProcessarCartaoJob::class, 1);
        Queue::assertPushed(ProcessarCartaoJob::class, fn ($j) => $j->queue === 'alta' && $j->connection === 'redis');
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_job_conclui_uma_vez_e_mostra_resultado_ao_autor(): void
    {
        $e = $this->enviar(aluno: $this->aluno);
        $j = $this->job($e);
        $d = $this->driver();
        $j->handle($d, app(ImagensCartao::class));
        $j->handle($d, app(ImagensCartao::class));
        $this->getJson($this->uri($e))->assertOk()->assertJsonPath('data.processamento', 'concluido')
            ->assertJsonPath('data.resultado.acertos', 2);
        $this->assertDatabaseCount('cartao_respostas', 2);
        $this->assertDatabaseCount('notas', 1);
    }

    public function test_ambiguas_seguem_para_revisao_sem_nota(): void
    {
        $e = $this->enviar(aluno: $this->aluno);
        $this->job($e)->handle($this->driver(ambigua: true), app(ImagensCartao::class));
        $this->getJson($this->uri($e))->assertOk()->assertJsonPath('data.resultado.ambiguas', 2)->assertJsonPath('data.resultado.status', 'ambiguo');
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_falha_e_nova_tentativa_reutilizam_o_cartao_e_ignoram_job_antigo(): void
    {
        $e = $this->enviar(aluno: $this->aluno);
        $j = $this->job($e);
        $d = Mockery::mock(OmrDriverInterface::class);
        $d->shouldReceive('processar')->once()->andThrow(new \RuntimeException('Falha simulada'));
        try {
            $j->handle($d, app(ImagensCartao::class));
            $this->fail('Deveria falhar');
        } catch (\RuntimeException $erro) {
            $j->failed($erro);
        }
        $this->getJson($this->uri($e))->assertOk()->assertJsonPath('data.processamento', 'erro')->assertJsonPath('data.pode_repetir', true);
        $this->postJson($this->uri($e).'/repetir')->assertStatus(202)->assertJsonPath('data.processamento', 'na_fila');
        $this->postJson($this->uri($e).'/repetir')->assertStatus(202);
        Queue::assertPushed(ProcessarCartaoJob::class, 2);
        $e->refresh();
        $this->assertEquals(2, $e->tentativa);
        $j->failed(new \RuntimeException('falha antiga'));
        $j->handle($d, app(ImagensCartao::class));
        $this->job($e)->handle($this->driver(), app(ImagensCartao::class));
        $this->assertDatabaseCount('cartoes', 1);
        $this->assertDatabaseCount('notas', 1);
    }

    public function test_foto_mais_nova_prevalece_mesmo_lida_antes_da_antiga(): void
    {
        $antigo = $this->enviar(aluno: $this->aluno);
        $novo = $this->enviar(aluno: $this->aluno);
        $this->job($novo)->handle($this->driver(), app(ImagensCartao::class));
        $this->job($antigo)->handle($this->driver(), app(ImagensCartao::class));
        $this->assertDatabaseCount('cartoes', 1);
        $this->assertDatabaseCount('notas', 1);
        $this->assertDatabaseHas('cartoes', ['id' => $novo->cartao_id]);
        $this->assertEquals('corrigida', $this->prova->fresh()->status);
        $this->getJson($this->uri($antigo))->assertJsonPath('data.processamento', 'substituido');
    }

    public function test_foto_nova_substitui_a_anterior_sem_resultado_obsoleto(): void
    {
        $antigo = $this->enviar(aluno: $this->aluno);
        $this->job($antigo)->handle($this->driver(), app(ImagensCartao::class));
        $novo = $this->enviar(aluno: $this->aluno);
        $this->job($novo)->handle($this->driver(), app(ImagensCartao::class));
        $this->getJson($this->uri($antigo))->assertJsonPath('data.processamento', 'substituido')->assertJsonPath('data.resultado', []);
        $this->assertDatabaseCount('notas', 1);
    }

    public function test_qr_redireciona_somente_para_prova_acessivel_da_mesma_escola(): void
    {
        $destino = $this->novaProva($this->prova->escola_id);
        $e = $this->enviar();
        $this->job($e)->handle($this->driver(['prova_id' => $destino->id, 'aluno_id' => $this->aluno]), app(ImagensCartao::class));
        $this->assertDatabaseHas('cartoes', ['id' => $e->cartao_id, 'prova_id' => $destino->id, 'aluno_id' => $this->aluno]);
        $this->getJson($this->uri($e))->assertJsonPath('data.resultado.prova_corrigida', 'Matemática');
    }

    public function test_qr_fora_do_escopo_e_recusado_sem_apagar_o_recibo(): void
    {
        $professor = $this->usuario('professor');
        DB::table('usuario_escopos')->insert(['usuario_id' => $professor->id, 'escopo_tipo' => 'escola', 'escopo_id' => $this->prova->escola_id]);
        $destino = $this->novaProva($this->prova->escola_id);
        $this->prova->update(['criado_por' => $professor->id]);
        Sanctum::actingAs($professor);
        $e = $this->enviar();
        $this->job($e)->handle($this->driver(['prova_id' => $destino->id]), app(ImagensCartao::class));
        $this->getJson($this->uri($e))->assertOk()->assertJsonPath('data.processamento', 'recusado')->assertJsonPath('data.pode_repetir', false);
        $this->assertDatabaseCount('cartoes', 0);
    }

    public function test_aplicador_acompanha_o_proprio_envio_sem_notas(): void
    {
        $u = $this->usuario('aplicador');
        DB::table('usuario_escopos')->insert(['usuario_id' => $u->id, 'escopo_tipo' => 'escola', 'escopo_id' => $this->prova->escola_id]);
        Sanctum::actingAs($u);
        $e = $this->enviar(aluno: $this->aluno);
        $this->job($e)->handle($this->driver(), app(ImagensCartao::class));
        $this->getJson($this->uri($e))->assertOk()->assertJsonPath('data.resultado.aluno_nome', 'Aluno fictício')
            ->assertJsonMissingPath('data.resultado.nota_final')->assertJsonMissingPath('data.resultado.acertos');
    }

    public function test_outro_usuario_nao_consulta_nem_repete_o_envio(): void
    {
        $e = $this->enviar();
        Sanctum::actingAs($this->usuario());
        $this->getJson($this->uri($e))->assertNotFound();
        $this->postJson($this->uri($e).'/repetir')->assertNotFound();
    }

    public function test_fechamento_antes_da_leitura_recusa_sem_processar(): void
    {
        $u = $this->usuario('professor');
        DB::table('usuario_escopos')->insert(['usuario_id' => $u->id, 'escopo_tipo' => 'escola', 'escopo_id' => $this->prova->escola_id]);
        $this->prova->update(['criado_por' => $u->id]);
        Sanctum::actingAs($u);
        $e = $this->enviar();
        $this->prova->update(['correcao_fechada_em' => now()]);
        $d = Mockery::mock(OmrDriverInterface::class);
        $d->shouldNotReceive('processar');
        $this->job($e)->handle($d, app(ImagensCartao::class));
        $this->getJson($this->uri($e))->assertJsonPath('data.processamento', 'recusado');
    }

    public function test_timeout_e_menor_que_o_tempo_de_reentrega(): void
    {
        $e = $this->enviar();
        $this->assertGreaterThan($this->job($e)->timeout, config('queue.connections.redis.retry_after'));
        $this->assertGreaterThan($this->job($e)->timeout, config('queue.connections.database.retry_after'));
    }

    public function test_erro_na_gravacao_preserva_leitura_nota_e_foto_anteriores(): void
    {
        $antigo = $this->enviar(aluno: $this->aluno);
        $this->job($antigo)->handle($this->driver(), app(ImagensCartao::class));
        $foto = Cartao::find($antigo->cartao_id)->imagem_url;
        $novo = $this->enviar(aluno: $this->aluno);
        $d = Mockery::mock(OmrDriverInterface::class);
        $linha = ['numero_questao' => 1, 'alternativa' => 'A', 'confianca' => 1, 'ambigua' => false,
            'multiplas_marcacoes' => false, 'alternativas_detectadas' => []];
        $d->shouldReceive('processar')->andReturn([$linha, $linha]); // Viola a chave única de questão.
        try {
            $this->job($novo)->handle($d, app(ImagensCartao::class));
            $this->fail('A gravação deveria falhar');
        } catch (QueryException) {
        }
        $this->assertDatabaseHas('notas', ['cartao_id' => $antigo->cartao_id]);
        $this->assertDatabaseHas('cartoes', ['id' => $novo->cartao_id, 'status' => 'pendente']);
        $this->assertEquals('concluido', $antigo->fresh()->status);
        Storage::disk('public')->assertExists($foto);
    }

    public function test_envio_interrompido_pode_ser_recuperado_sem_novo_cartao(): void
    {
        $e = $this->enviar();
        $e->update(['updated_at' => now()->subMinutes(11)]);
        $this->getJson($this->uri($e))->assertOk()->assertJsonPath('data.pode_repetir', true);
        $this->postJson($this->uri($e).'/repetir')->assertStatus(202);
        $this->assertEquals(2, $e->fresh()->tentativa);
        $this->assertDatabaseCount('cartoes', 1);
    }

    public function test_foto_nova_substitui_falha_definitiva_sem_deixar_pendencia(): void
    {
        $antigo = $this->enviar(aluno: $this->aluno);
        $jobAntigo = $this->job($antigo);
        $jobAntigo->failed(new \RuntimeException('Falha fictícia'));
        $novo = $this->enviar(aluno: $this->aluno);
        $this->job($novo)->handle($this->driver(), app(ImagensCartao::class));

        $this->getJson($this->uri($antigo))->assertOk()
            ->assertJsonPath('data.processamento', 'substituido')->assertJsonPath('data.pode_repetir', false);
        $this->getJson('/api/v1/provas/'.$this->prova->id.'/cartoes/status')->assertOk()
            ->assertJsonPath('data.pendentes', 0)->assertJsonPath('data.concluida', true);
        $jobAntigo->handle($this->driver(), app(ImagensCartao::class));
        $jobAntigo->failed(new \RuntimeException('Falha atrasada'));
        $this->assertDatabaseCount('cartoes', 1);
        $this->assertDatabaseCount('notas', 1);
        $this->assertDatabaseHas('notas', ['cartao_id' => $novo->cartao_id]);
        $this->assertEquals('substituido', $antigo->fresh()->status);
    }

    public function test_leitura_antiga_nao_descarta_foto_mais_nova_com_erro(): void
    {
        $antigo = $this->enviar(aluno: $this->aluno);
        $novo = $this->enviar(aluno: $this->aluno);
        $this->job($novo)->failed(new \RuntimeException('Falha fictícia'));
        $this->job($antigo)->handle($this->driver(), app(ImagensCartao::class));
        $this->assertDatabaseCount('cartoes', 2);
        $this->getJson($this->uri($novo))->assertOk()
            ->assertJsonPath('data.processamento', 'erro')->assertJsonPath('data.pode_repetir', true);
        $this->postJson($this->uri($novo).'/repetir')->assertStatus(202);
        $this->job($novo->fresh())->handle($this->driver(), app(ImagensCartao::class));
        $this->assertDatabaseCount('cartoes', 1);
        $this->assertDatabaseHas('notas', ['cartao_id' => $novo->cartao_id]);
    }

    public function test_excluir_cartao_limpa_recibo_e_uuid_nao_ressuscita_resultado(): void
    {
        $e = $this->enviar(aluno: $this->aluno);
        $this->job($e)->handle($this->driver(), app(ImagensCartao::class));
        $this->deleteJson('/api/v1/cartoes/'.$e->cartao_id)->assertOk();
        $this->assertDatabaseCount('cartoes', 0);
        $this->assertDatabaseCount('notas', 0);
        $this->assertNull($e->fresh()->resultado);
        $this->getJson($this->uri($e))->assertOk()->assertJsonPath('data.processamento', 'recusado')
            ->assertJsonPath('data.resultado', [])->assertJsonPath('data.pode_repetir', false);
        $this->postJson($this->uri($e).'/repetir')->assertStatus(202)->assertJsonPath('data.resultado', []);
        $this->postJson('/api/v1/provas/'.$this->prova->id.'/cartoes', ['envio_id' => $e->chave,
            'imagem' => UploadedFile::fake()->image('cartao.jpg')])->assertStatus(202)
            ->assertJsonPath('data.processamento', 'recusado')->assertJsonPath('data.resultado', []);
        $this->assertDatabaseCount('cartoes', 0);
        Queue::assertPushed(ProcessarCartaoJob::class, 1);
    }

    public function test_recibo_antigo_com_cartao_excluido_nao_devolve_resultado(): void
    {
        $e = $this->enviar(aluno: $this->aluno);
        $this->job($e)->handle($this->driver(), app(ImagensCartao::class));
        // Simula exclusão anterior à correção: a FK é anulada, mas o JSON antigo fica.
        Cartao::findOrFail($e->cartao_id)->delete();
        $this->getJson($this->uri($e))->assertOk()->assertJsonPath('data.processamento', 'recusado')
            ->assertJsonPath('data.resultado', [])->assertJsonPath('data.pode_repetir', false);
        $this->assertNull($e->fresh()->resultado);
    }

    public function test_fila_indisponivel_preserva_foto_e_permite_repetir(): void
    {
        Bus::shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('Fila indisponível simulada'));
        $chave = (string) Str::uuid();
        $this->postJson('/api/v1/provas/'.$this->prova->id.'/cartoes', ['envio_id' => $chave,
            'imagem' => UploadedFile::fake()->image('cartao.jpg')])->assertStatus(202)
            ->assertJsonPath('data.processamento', 'erro')->assertJsonPath('data.pode_repetir', true);
        $this->assertDatabaseCount('cartoes', 1);
        $cartao = Cartao::first();
        Storage::disk('public')->assertExists($cartao->imagem_url);
    }

    public function test_upload_repetido_nao_revela_resultado_de_destino_que_saiu_do_escopo(): void
    {
        $u = $this->usuario('professor');
        DB::table('usuario_escopos')->insert([
            ['usuario_id' => $u->id, 'escopo_tipo' => 'escola', 'escopo_id' => $this->prova->escola_id],
            ['usuario_id' => $u->id, 'escopo_tipo' => 'turma', 'escopo_id' => $this->turma],
        ]);
        $this->prova->update(['criado_por' => $u->id]);
        $destino = $this->novaProva($this->prova->escola_id);
        Sanctum::actingAs($u);
        $e = $this->enviar();
        $this->job($e)->handle($this->driver(['prova_id' => $destino->id, 'aluno_id' => $this->aluno]), app(ImagensCartao::class));
        DB::table('usuario_escopos')->where('usuario_id', $u->id)->where('escopo_tipo', 'turma')->delete();
        $this->getJson($this->uri($e))->assertNotFound();
        $this->postJson($this->uri($e).'/repetir')->assertNotFound();
        $this->postJson('/api/v1/provas/'.$this->prova->id.'/cartoes', ['envio_id' => $e->chave,
            'imagem' => UploadedFile::fake()->image('cartao.jpg')])->assertNotFound();
        $this->assertDatabaseCount('cartoes', 1);
    }
}
