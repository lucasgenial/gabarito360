<?php

namespace Tests\Feature;

use App\Jobs\ProcessarCartaoJob;
use App\Models\CartaoEnvio;
use App\Models\Usuario;
use App\Services\Omr\ImagensCartao;
use App\Services\Omr\OmrDriverInterface;
use App\Services\Omr\OmrIdentificacaoInterface;
use App\Services\Omr\ResultadoEnvio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class FilaVinculoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo' => true]);
        Queue::fake([ProcessarCartaoJob::class]);
        Storage::fake('public');
        \App\Support\Permissao::limparCache();
        DB::table('usuarios')->insert(['id' => 1, 'perfil' => 'admin_rede', 'nome' => 'Fictício', 'email' => 'v1@example.test', 'cpf' => '00000000001', 'password' => 'ficticio', 'ativo' => true]);
        DB::table('clientes')->insert(['id' => 1, 'tipo' => 'rede', 'nome' => 'Cliente', 'modalidade' => 'interno', 'situacao' => 'ativo']);
        DB::table('redes')->insert(['id' => 1, 'nome' => 'Rede', 'tipo' => 'estadual', 'uf' => 'BA']);
        DB::table('nucleos')->insert(['id' => 1, 'rede_id' => 1, 'nome' => 'Núcleo']);
        DB::table('espacos')->insert([
            ['id' => 1, 'cliente_id' => 1, 'tipo' => 'rede', 'nome' => 'Rede', 'situacao' => 'ativo', 'rede_id' => 1, 'nucleo_id' => null],
            ['id' => 2, 'cliente_id' => 1, 'tipo' => 'nucleo', 'nome' => 'Núcleo', 'situacao' => 'ativo', 'rede_id' => null, 'nucleo_id' => 1],
        ]);
        DB::table('usuario_escopos')->insert(['usuario_id' => 1, 'escopo_tipo' => 'rede', 'escopo_id' => 1]);
        foreach ([1, 2] as $id) {
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => 1, 'nome' => 'Escola', 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            DB::table('espacos')->insert(['id' => $id + 2, 'cliente_id' => 1, 'tipo' => 'escola', 'nome' => 'Escola', 'situacao' => 'ativo', 'escola_id' => $id]);
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 2, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            DB::table('alunos')->insert(['id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 2, 'nome' => 'Fictício', 'matricula' => 'F-'.$id]);
            DB::table('provas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 2, 'criado_por' => 1, 'titulo' => 'Prova', 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 4, 'status' => 'publicada', 'data_aplicacao' => now()->toDateString()]);
            DB::table('prova_turmas')->insert(['prova_id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 2]);
            DB::table('gabaritos')->insert(['id' => $id, 'prova_id' => $id, 'espaco_id' => $id + 2]);
            DB::table('gabarito_questoes')->insert(['gabarito_id' => $id, 'espaco_id' => $id + 2, 'numero_questao' => 1, 'alternativa' => 'A']);
        }
        foreach ([1 => ['professor', 3], 2 => ['aplicador', 3], 3 => ['professor', 4]] as $id => [$perfil, $espaco]) {
            DB::table('usuario_vinculos')->insert(['id' => $id, 'usuario_id' => 1, 'espaco_id' => $espaco, 'perfil' => $perfil, 'situacao' => 'ativo', 'inicio_em' => now()->subDay()]);
        }
        DB::table('vinculo_turmas')->insert([['vinculo_id' => 1, 'espaco_id' => 3, 'turma_id' => 1], ['vinculo_id' => 3, 'espaco_id' => 4, 'turma_id' => 2]]);
        $this->mock(ImagensCartao::class, fn ($m) => $m->shouldReceive('gerar')->andReturn(true));
    }

    private function ator(int $v = 1): Usuario
    {
        return Usuario::findOrFail(1)->noVinculo($v);
    }

    private function enviar(int $v = 1, ?string $chave = null): CartaoEnvio
    {
        $chave ??= (string) Str::uuid();
        Sanctum::actingAs($this->ator($v));
        $this->postJson('/api/v1/provas/1/cartoes', ['envio_id' => $chave, 'imagem' => UploadedFile::fake()->image('cartao.jpg'), 'aluno_id' => 1])
            ->assertStatus(202)->assertJsonPath('data.envio_id', $chave);
        return CartaoEnvio::where('vinculo_id', $v)->where('chave_cliente', $chave)->firstOrFail();
    }

    private function job(CartaoEnvio $e): ProcessarCartaoJob
    {
        return new ProcessarCartaoJob($e->cartao_id, $e->id, $e->tentativa, $e->espaco_id, $e->usuario_id, $e->vinculo_id);
    }

    private function driver(?callable $durante = null, array $qr = []): OmrDriverInterface
    {
        $d = Mockery::mock(OmrDriverInterface::class.','.OmrIdentificacaoInterface::class);
        $d->shouldReceive('processar')->once()->andReturnUsing(function () use ($durante) {
            if ($durante) $durante();
            return [['numero_questao' => 1, 'alternativa' => 'A', 'confianca' => 1, 'ambigua' => false, 'multiplas_marcacoes' => false, 'alternativas_detectadas' => []]];
        });
        $d->shouldReceive('identificacao')->andReturn($qr);
        return $d;
    }

    public function test_mesma_chave_publica_em_dois_vinculos_gera_recibos_isolados(): void
    {
        $chave = (string) Str::uuid();
        $a = $this->enviar(1, $chave);
        $this->assertSame($a->id, $this->enviar(1, $chave)->id);
        $b = $this->enviar(2, $chave);
        $this->assertNotSame($a->id, $b->id);
        $this->assertNotSame($a->chave, $b->chave);
        $this->assertDatabaseCount('cartao_envios', 2);
        Queue::assertPushed(ProcessarCartaoJob::class, fn ($job) => $job->vinculoId === 2 && $job->envioId === $b->id);
        $a->update(['mensagem' => 'apenas vínculo 1']);
        Sanctum::actingAs($this->ator(2));
        $this->getJson('/api/v1/provas/1/cartoes/envios/'.$chave)->assertOk()->assertJsonPath('data.mensagem', null);
        Sanctum::actingAs(Usuario::find(1));
        $this->getJson('/api/v1/provas/1/cartoes/envios/'.$chave)->assertNotFound();
    }

    public function test_polling_e_retentativa_nao_usam_recibo_de_outro_vinculo(): void
    {
        $a = $this->enviar();
        $a->update(['status' => 'erro']);
        Sanctum::actingAs($this->ator(2));
        $uri = '/api/v1/provas/1/cartoes/envios/'.$a->chave_cliente;
        $this->getJson($uri)->assertNotFound();
        $this->postJson($uri.'/repetir')->assertNotFound();
        Sanctum::actingAs($this->ator(1));
        $this->postJson($uri.'/repetir')->assertStatus(202);
        $this->assertSame(2, $a->fresh()->tentativa);
    }

    public function test_job_valido_conclui_com_o_contexto_serializado(): void
    {
        $a = $this->enviar();
        $job = unserialize(serialize($this->job($a)));
        $this->assertSame(1, $job->vinculoId);
        $job->handle($this->driver(), app(ImagensCartao::class));
        $this->assertSame('concluido', $a->fresh()->status);
        $this->assertDatabaseCount('notas', 1);
        $this->assertSame($a->chave_cliente, ResultadoEnvio::resposta($a->fresh(), $this->ator())['envio_id']);
    }

    public function test_revogacao_antes_da_leitura_recusa_sem_chamar_omr(): void
    {
        $a = $this->enviar();
        DB::table('usuario_vinculos')->where('id', 1)->update(['situacao' => 'suspenso']);
        $d = Mockery::mock(OmrDriverInterface::class);
        $d->shouldNotReceive('processar');
        $this->job($a)->handle($d, app(ImagensCartao::class));
        $this->assertSame('recusado', $a->fresh()->status);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_revogacao_durante_leitura_nao_grava_resultado_nem_reagenda(): void
    {
        $a = $this->enviar();
        $this->job($a)->handle($this->driver(fn () => DB::table('usuario_vinculos')->where('id', 1)->update(['situacao' => 'encerrado'])), app(ImagensCartao::class));
        $this->assertSame('recusado', $a->fresh()->status);
        $this->assertNull($a->fresh()->resultado);
        $this->assertDatabaseCount('cartao_respostas', 0);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_qr_nao_usa_alcance_do_admin_legado_para_outra_escola(): void
    {
        $a = $this->enviar();
        $this->job($a)->handle($this->driver(qr: ['prova_id' => 2, 'aluno_id' => 2]), app(ImagensCartao::class));
        $this->assertSame('recusado', $a->fresh()->status);
        $this->assertDatabaseHas('cartoes', ['id' => $a->cartao_id, 'prova_id' => 1]);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_payload_sem_contexto_ou_com_contexto_errado_nao_altera_recibo(): void
    {
        $a = $this->enviar();
        $d = Mockery::mock(OmrDriverInterface::class);
        $d->shouldNotReceive('processar');
        foreach ([null, 2] as $id) {
            $job = new ProcessarCartaoJob($a->cartao_id, $a->id, 1, $a->espaco_id, 1, $id);
            $job->handle($d, app(ImagensCartao::class));
            $job->failed(null);
        }
        $this->assertSame('na_fila', $a->fresh()->status);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_job_legado_sem_propriedade_vinculo_continua_compativel(): void
    {
        Sanctum::actingAs(Usuario::find(1));
        $chave = (string) Str::uuid();
        $this->postJson('/api/v1/provas/1/cartoes', ['envio_id' => $chave, 'imagem' => UploadedFile::fake()->image('legado.jpg'), 'aluno_id' => 1])->assertStatus(202);
        $e = CartaoEnvio::where('chave', $chave)->firstOrFail();
        $this->assertNull($e->vinculo_id);
        $job = new ProcessarCartaoJob($e->cartao_id, $e->id, 1, $e->espaco_id, 1);
        unset($job->vinculoId);
        $job = unserialize(serialize($job));
        $job->handle($this->driver(), app(ImagensCartao::class));
        $this->assertSame('concluido', $e->fresh()->status);
    }
}
