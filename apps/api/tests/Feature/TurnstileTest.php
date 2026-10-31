<?php

namespace Tests\Feature;

use App\Mail\EmailModelo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile.enabled' => true, 'services.turnstile.secret_key' => 'segredo-ficticio',
            'services.turnstile.hostnames' => ['gabarito360.com.br']]);
        Mail::fake();
        Http::preventStrayRequests();
    }

    private function contato(): array
    {
        return ['nome' => 'Pessoa Teste', 'email' => 'pessoa@example.test', 'mensagem' => 'Quero conhecer o sistema.',
            'cf-turnstile-response' => 'token-ficticio'];
    }

    public function test_sem_token_nao_grava_nem_envia_email(): void
    {
        $dados = $this->contato();
        unset($dados['cf-turnstile-response']);
        $this->postJson('/api/v1/leads', $dados)->assertUnprocessable();
        $this->assertDatabaseCount('leads', 0);
        Mail::assertNothingQueued();
        Http::assertNothingSent();
    }

    public function test_token_valido_grava_e_enfileira_emails(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response([
            'success' => true, 'hostname' => 'gabarito360.com.br', 'action' => 'fale-conosco',
        ])]);
        $this->postJson('/api/v1/leads', $this->contato())->assertCreated();
        $this->assertDatabaseCount('leads', 1);
        Mail::assertQueued(EmailModelo::class, 2);
        Http::assertSent(fn ($r) => $r->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $r['secret'] === 'segredo-ficticio' && $r['response'] === 'token-ficticio');
    }

    public static function recusas(): array
    {
        return [
            'expirado ou reutilizado' => [['success' => false, 'error-codes' => ['timeout-or-duplicate']]],
            'outro dominio' => [['success' => true, 'hostname' => 'outro.example', 'action' => 'fale-conosco']],
            'outra acao' => [['success' => true, 'hostname' => 'gabarito360.com.br', 'action' => 'login']],
            'resposta incompleta' => [['success' => true]],
            'resposta invalida' => [[]],
        ];
    }

    #[DataProvider('recusas')]
    public function test_recusa_nao_tem_efeitos_colaterais(array $resposta): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response($resposta)]);
        $this->postJson('/api/v1/leads', $this->contato())->assertUnprocessable();
        $this->assertDatabaseCount('leads', 0);
        Mail::assertNothingQueued();
    }

    public function test_indisponibilidade_nao_libera_envio(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::failedConnection()]);
        $this->postJson('/api/v1/leads', $this->contato())->assertStatus(503);
        $this->assertDatabaseCount('leads', 0);
        Mail::assertNothingQueued();
    }

    public function test_sem_chave_nao_libera_envio(): void
    {
        config(['services.turnstile.secret_key' => null]);
        $this->postJson('/api/v1/leads', $this->contato())->assertStatus(503);
        $this->assertDatabaseCount('leads', 0);
        Mail::assertNothingQueued();
        Http::assertNothingSent();
    }

    public function test_token_maior_que_o_limite_nao_e_enviado_ao_provedor(): void
    {
        $dados = $this->contato();
        $dados['cf-turnstile-response'] = str_repeat('x', 2049);
        $this->postJson('/api/v1/leads', $dados)->assertUnprocessable();
        Http::assertNothingSent();
        $this->assertDatabaseCount('leads', 0);
        Mail::assertNothingQueued();
    }

    public function test_erro_http_nao_libera_envio(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response([], 500)]);
        $this->postJson('/api/v1/leads', $this->contato())->assertStatus(503);
        $this->assertDatabaseCount('leads', 0);
        Mail::assertNothingQueued();
    }
}
