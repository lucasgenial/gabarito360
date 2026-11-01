<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Cartao;
use App\Models\CartaoEnvio;
use App\Jobs\ProcessarCartaoJob;
use App\Services\Omr\ImagensCartao;
use App\Services\Omr\OmrDriverInterface;
use App\Services\Omr\OmrIdentificacaoInterface;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class CartoesEspacosTest extends TestCase
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


    private function enviar(int $prova = 1, ?string $chave = null): CartaoEnvio
    {
        $chave ??= (string) Str::uuid();
        $this->postJson("/api/v1/provas/{$prova}/cartoes", ['envio_id' => $chave, 'aluno_id' => $prova,
            'imagem' => UploadedFile::fake()->image('cartao.jpg')])->assertStatus(202);
        return CartaoEnvio::where('chave', $chave)->firstOrFail();
    }

    private function job(CartaoEnvio $e): ProcessarCartaoJob
    {
        return new ProcessarCartaoJob($e->cartao_id, $e->id, $e->tentativa, $e->espaco_id, $e->usuario_id);
    }

    private function driver(array $qr = [], ?callable $durante = null): OmrDriverInterface
    {
        $d = Mockery::mock(OmrDriverInterface::class.','.OmrIdentificacaoInterface::class);
        $d->shouldReceive('processar')->andReturnUsing(function () use ($durante) {
            if ($durante) $durante();
            return [['numero_questao' => 1, 'alternativa' => 'A', 'confianca' => 1, 'ambigua' => false,
                'multiplas_marcacoes' => false, 'alternativas_detectadas' => []]];
        });
        $d->shouldReceive('identificacao')->andReturn($qr);
        return $d;
    }

    private function processar(CartaoEnvio $e, ?OmrDriverInterface $d = null): void
    {
        $imagens = Mockery::mock(ImagensCartao::class);
        $imagens->shouldReceive('gerar')->andReturn(true);
        $imagens->shouldReceive('apagar')->andReturnNull();
        $this->job($e)->handle($d ?? $this->driver(), $imagens);
    }

    public function test_fila_propaga_espaco_autoria_e_idempotencia_ate_nota(): void
    {
        $e = $this->enviar();
        $this->enviar(chave: $e->chave);
        Queue::assertPushed(ProcessarCartaoJob::class, 1);
        Queue::assertPushed(ProcessarCartaoJob::class, fn ($j) => $j->espacoId === $e->espaco_id && $j->atorId === $this->admin->id);
        $this->processar($e);
        $this->processar($e);
        foreach (['cartoes', 'cartao_envios', 'cartao_respostas', 'notas'] as $t) {
            $this->assertDatabaseCount($t, 1);
            $this->assertDatabaseHas($t, ['espaco_id' => $e->espaco_id, 'criado_por' => $this->admin->id, 'origem_autoria' => 'registrada']);
        }
        $this->getJson("/api/v1/provas/1/cartoes/envios/{$e->chave}")->assertOk()->assertJsonPath('data.resultado.acertos', 1);
        $this->assertStringStartsWith("cartoes/espacos/{$e->espaco_id}/1/", Cartao::find($e->cartao_id)->imagem_url);
    }

    public function test_redes_separadas_e_administrador_duplo_nao_permite_vinculo_cruzado(): void
    {
        $this->getJson('/api/v1/provas/2/cartoes')->assertNotFound();
        $e = $this->enviar();
        $this->escopo($this->admin, 'rede', 2);
        $this->putJson('/api/v1/cartoes/'.$e->cartao_id.'/vincular-aluno', ['aluno_id' => 2])->assertNotFound();
        $this->postJson('/api/v1/provas/2/cartoes', ['envio_id' => $e->chave, 'imagem' => UploadedFile::fake()->image('c.jpg')])->assertConflict();
        $this->assertDatabaseHas('cartoes', ['id' => $e->cartao_id, 'aluno_id' => 1]);
    }

    public function test_revogacao_antes_da_fila_preserva_foto_e_nao_processa(): void
    {
        $e = $this->enviar();
        $foto = Cartao::find($e->cartao_id)->imagem_url;
        DB::table('usuario_escopos')->where('usuario_id', $this->admin->id)->delete();
        $d = Mockery::mock(OmrDriverInterface::class); $d->shouldNotReceive('processar');
        $this->processar($e, $d);
        $this->assertSame('recusado', $e->fresh()->status);
        $this->assertDatabaseCount('notas', 0);
        Storage::disk('public')->assertExists($foto);
        $this->getJson("/api/v1/provas/1/cartoes/envios/{$e->chave}")->assertNotFound();
    }

    public function test_suspensao_durante_omr_impede_efeitos(): void
    {
        $e = $this->enviar();
        $this->processar($e, $this->driver(durante: fn () => DB::table('espacos')->where('id', $e->espaco_id)->update(['situacao' => 'suspenso'])));
        $this->assertSame('recusado', $e->fresh()->status);
        $this->assertDatabaseCount('cartao_respostas', 0);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_job_com_contexto_incorreto_nao_altera_recibo(): void
    {
        $e = $this->enviar();
        $j = new ProcessarCartaoJob($e->cartao_id, $e->id, 1, $this->espacos[2]['escola'], $this->admin->id);
        $d = Mockery::mock(OmrDriverInterface::class); $d->shouldNotReceive('processar');
        $j->handle($d, new ImagensCartao());
        $j->failed(new \RuntimeException('fictícia'));
        $this->assertSame('na_fila', $e->fresh()->status);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_qr_nao_transfere_entre_espacos_mesmo_com_acesso_as_duas_redes(): void
    {
        $e = $this->enviar();
        $this->escopo($this->admin, 'rede', 2);
        $this->processar($e, $this->driver(['prova_id' => 2, 'aluno_id' => 2]));
        $this->assertSame('recusado', $e->fresh()->status);
        $this->assertDatabaseHas('cartoes', ['id' => $e->cartao_id, 'prova_id' => 1, 'aluno_id' => 1]);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_link_assinado_revalida_acesso_e_aceita_arquivo_legado_mapeado(): void
    {
        $e = $this->enviar();
        Storage::disk('public')->put('cartoes/derivados/'.$e->cartao_id.'-miniatura.jpg', 'fictício');
        $url = (new ImagensCartao())->url($e->cartao_id, 'miniatura', $this->admin);
        $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get($url.'&espaco=999')->assertForbidden();
        DB::table('usuario_escopos')->where('usuario_id', $this->admin->id)->delete();
        $this->get($url)->assertNotFound();
    }

    public function test_revisao_grava_autoria_e_rollback_se_filho_divergente(): void
    {
        $e = $this->enviar(); $this->processar($e);
        $uri = '/api/v1/cartoes/'.$e->cartao_id.'/revisar';
        $this->postJson($uri, ['numero_questao' => 1, 'alternativa' => 'B'])->assertOk();
        $this->assertDatabaseHas('ambiguidade_logs', ['espaco_id' => $e->espaco_id, 'criado_por' => $this->admin->id]);
        DB::table('gabarito_questoes')->where('gabarito_id', 1)->update(['espaco_id' => $this->espacos[2]['escola']]);
        $this->postJson($uri, ['numero_questao' => 1, 'alternativa' => 'A'])->assertConflict();
        $this->assertDatabaseHas('cartao_respostas', ['cartao_id' => $e->cartao_id, 'alternativa' => 'B']);
        $this->assertDatabaseCount('ambiguidade_logs', 1);
    }

    public function test_gabarito_novo_herda_autoria_e_nao_expoe_questao_de_outro_espaco(): void
    {
        DB::table('gabaritos')->where('id', 1)->delete();
        $this->postJson('/api/v1/provas/1/gabarito', ['questoes' => [['numero_questao' => 1, 'alternativa' => 'A']]])->assertOk();
        $g = DB::table('gabaritos')->where('prova_id', 1)->first();
        $this->assertEquals($this->espacos[1]['escola'], $g->espaco_id);
        $this->assertEquals($this->admin->id, $g->criado_por);
        DB::table('gabarito_questoes')->where('gabarito_id', $g->id)->update(['espaco_id' => $this->espacos[2]['escola']]);
        $this->getJson('/api/v1/provas/1/gabarito')->assertOk()->assertJsonCount(0, 'data.questoes');
        $this->postJson('/api/v1/provas/1/gabarito/publicar')->assertUnprocessable();
    }

    public function test_recibo_com_cartao_de_outro_espaco_nao_e_exposto_nem_retentado(): void
    {
        $a = $this->enviar(); $this->escopo($this->admin, 'rede', 2); $b = $this->enviar(2);
        $a->update(['cartao_id' => $b->cartao_id, 'status' => 'erro']);
        $uri = "/api/v1/provas/1/cartoes/envios/{$a->chave}";
        $this->getJson($uri)->assertNotFound();
        $this->postJson($uri.'/repetir')->assertNotFound();
        $this->assertSame('erro', $a->fresh()->status);
    }

    public function test_comando_exige_espaco_e_simula_sem_gerar_arquivos(): void
    {
        $e = $this->enviar();
        $this->mock(ImagensCartao::class)->shouldNotReceive('gerar');
        $this->artisan('g360:cartoes-imagens')->assertFailed();
        $this->artisan('g360:cartoes-imagens', ['--espaco' => $e->espaco_id])->assertSuccessful();
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_professor_nao_recebe_alunos_de_turma_nao_atribuida_da_mesma_prova(): void
    {
        $p = $this->usuario('professor');
        $this->escopo($p, 'escola', 1);
        $this->escopo($p, 'turma', 1);
        $meta = ['espaco_id' => $this->espacos[1]['escola']];
        DB::table('turmas')->insert(['id' => 3, 'escola_id' => 1, 'nome' => '3B', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026] + $meta);
        DB::table('alunos')->insert(['id' => 3, 'turma_id' => 3, 'nome' => 'Fora da turma', 'matricula' => 'FICTICIO-3'] + $meta);
        DB::table('prova_turmas')->insert(['prova_id' => 1, 'turma_id' => 3] + $meta);
        $ids = \App\Support\ContextoCorrecao::alunos(\App\Models\Prova::find(1), $p)->pluck('alunos.id')->all();
        $this->assertEquals([1], $ids);
        Sanctum::actingAs($p);
        $this->getJson('/api/v1/provas/1/cartoes/pdf?turma_id=3')->assertNotFound();
    }

    public function test_retentativa_preserva_contexto_e_resultado_individual_recusa_nota_cruzada(): void
    {
        $e = $this->enviar();
        $this->job($e)->failed(new \RuntimeException('fictícia'));
        $this->postJson("/api/v1/provas/1/cartoes/envios/{$e->chave}/repetir")->assertStatus(202);
        $e->refresh();
        $this->assertEquals(2, $e->tentativa);
        Queue::assertPushed(ProcessarCartaoJob::class, fn ($j) => $j->tentativa === 2 && $j->espacoId === $e->espaco_id);
        $this->processar($e);
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertOk();
        DB::table('notas')->where('cartao_id', $e->cartao_id)->update(['espaco_id' => $this->espacos[2]['escola']]);
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertNotFound();
        $this->getJson('/api/v1/provas/1/cartoes')->assertOk()->assertJsonPath('data.cartoes.0.nota_final', null);
    }
}
