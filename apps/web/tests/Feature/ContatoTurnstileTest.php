<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContatoTurnstileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile.enabled' => true, 'services.turnstile.site_key' => 'site-ficticio',
            'services.api.base_url' => 'http://api.test/api']);
        Http::preventStrayRequests();
    }

    private function contato(): array
    {
        return ['nome' => 'Pessoa Teste', 'email' => 'pessoa@example.test', 'assunto' => 'Suporte',
            'mensagem' => 'Mensagem de teste.', 'cf-turnstile-response' => 'token-ficticio'];
    }

    public function test_formulario_mostra_widget_com_acao_e_sem_segredo(): void
    {
        Http::fake(['api.test/api/v1/planos' => Http::response(['data' => []])]);
        $this->get('/fale-conosco')->assertOk()->assertSee('data-sitekey="site-ficticio"', false)
            ->assertSee('data-action="fale-conosco"', false)->assertSee('challenges.cloudflare.com/turnstile/v0/api.js', false);
    }

    public function test_envio_encaminha_token_a_api(): void
    {
        Http::fake(['api.test/api/v1/leads' => Http::response(['data' => []], 201)]);
        $this->post('/fale-conosco', $this->contato())->assertRedirect('/fale-conosco/obrigado');
        Http::assertSent(fn ($r) => $r['cf-turnstile-response'] === 'token-ficticio');
    }

    public function test_desativado_nao_carrega_o_widget(): void
    {
        config(['services.turnstile.enabled' => false]);
        Http::fake(['api.test/api/v1/planos' => Http::response(['data' => []])]);
        $this->get('/fale-conosco')->assertOk()->assertDontSee('challenges.cloudflare.com/turnstile/v0/api.js', false);
    }

    public function test_limite_no_envio_web(): void
    {
        Http::fake(['api.test/api/v1/leads' => Http::response(['data' => []], 201)]);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/fale-conosco', $this->contato())->assertRedirect('/fale-conosco/obrigado');
        }
        $this->post('/fale-conosco', $this->contato())->assertStatus(429);
        Http::assertSentCount(5);
    }

    public function test_token_ausente_bloqueia_envio(): void
    {
        $dados = $this->contato();
        unset($dados['cf-turnstile-response']);
        $this->from('/fale-conosco')->post('/fale-conosco', $dados)
            ->assertRedirect('/fale-conosco')->assertSessionHasErrors('cf-turnstile-response');
        Http::assertNothingSent();
    }

    public function test_erro_preserva_mensagem_sem_reaproveitar_token(): void
    {
        Http::fake(['api.test/api/v1/leads' => Http::response(['message' => 'Confirme a verificação.'], 422)]);
        $this->from('/fale-conosco')->post('/fale-conosco', $this->contato())
            ->assertRedirect('/fale-conosco')->assertSessionHasErrors('lead');
        $this->assertSame('Mensagem de teste.', session()->getOldInput('mensagem'));
        $this->assertArrayNotHasKey('cf-turnstile-response', session()->getOldInput());
    }

    public function test_validacao_local_nao_guarda_token(): void
    {
        $dados = $this->contato();
        $dados['email'] = 'invalido';
        $this->post('/fale-conosco', $dados)->assertSessionHasErrors('email');
        $this->assertArrayNotHasKey('cf-turnstile-response', session()->getOldInput());
        Http::assertNothingSent();
    }
}
