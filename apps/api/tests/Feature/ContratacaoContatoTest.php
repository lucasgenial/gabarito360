<?php

namespace Tests\Feature;

use App\Models\Plano;
use App\Models\Usuario;
use App\Services\MercadoPagoService;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContratacaoContatoTest extends TestCase
{
    use RefreshDatabase;

    public function test_contato_aceita_interesse_em_plano_de_autoatendimento(): void
    {
        Mail::fake();
        config(['services.turnstile.enabled' => false]);
        $this->seed(PlanoSeeder::class);
        $plano = Plano::where('modo', 'autoatendimento')->firstOrFail();
        $this->postJson('/api/v1/leads', [
            'plano_id' => $plano->id, 'nome' => 'Pessoa Teste', 'email' => 'teste@example.test',
            'assunto' => 'Contratação', 'mensagem' => 'Quero conhecer este plano.',
        ])->assertCreated();
        $this->assertDatabaseHas('leads', ['plano_id' => $plano->id, 'email' => 'teste@example.test']);
        $this->assertDatabaseCount('assinaturas', 0);
    }

    public function test_checkout_da_api_nao_cria_assinatura_nem_acessa_gateway(): void
    {
        Http::preventStrayRequests();
        $gateway = \Mockery::mock(MercadoPagoService::class);
        $gateway->shouldNotReceive('criarAssinaturaRecorrente');
        $gateway->shouldNotReceive('criarPreferenciaCheckout');
        $this->app->instance(MercadoPagoService::class, $gateway);
        Sanctum::actingAs(Usuario::create([
            'perfil' => 'professor', 'nome' => 'Pessoa Teste', 'email' => 'teste@example.test',
            'cpf' => '00000000002', 'password' => 'senha-ficticia', 'ativo' => true,
        ]));
        $this->postJson('/api/v1/assinaturas/checkout', ['plano_id' => 1])->assertStatus(409)
            ->assertJsonPath('message', 'Para contratar, fale com nossa equipe pelo Fale Conosco.');
        $this->assertDatabaseCount('assinaturas', 0);
        Http::assertNothingSent();
    }
}
