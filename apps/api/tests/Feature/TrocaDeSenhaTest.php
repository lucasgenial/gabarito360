<?php

namespace Tests\Feature;

use App\Mail\EmailModelo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Arquivo: apps/api/tests/Feature/TrocaDeSenhaTest.php
 * Função: Troca obrigatória de senha e link enviado pelo gestor.
 */
class TrocaDeSenhaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $perfil, string $email, array $extra = []): Usuario
    {
        return Usuario::create([
            'perfil' => $perfil, 'nome' => 'Pessoa Teste', 'email' => $email,
            'cpf' => substr(md5($email), 0, 11), 'password' => Hash::make('senha-antiga'), 'ativo' => true,
        ] + $extra);
    }

    private function entrar(string $email, string $senha = 'senha-antiga')
    {
        return $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $senha])->assertOk();
    }

    public function test_login_sem_pendencia_nao_pede_troca(): void
    {
        $this->usuario('professor', 'a@gabarito360.dev');

        $this->entrar('a@gabarito360.dev')->assertJsonPath('data.trocar_senha', false);
    }

    public function test_pendencia_bloqueia_a_api_ate_trocar_a_senha(): void
    {
        $usuario = $this->usuario('professor', 'b@gabarito360.dev', ['forcar_troca_senha' => true]);

        $login = $this->entrar('b@gabarito360.dev')->assertJsonPath('data.trocar_senha', true);
        $cabecalho = ['Authorization' => 'Bearer ' . $login->json('data.token')];

        $this->getJson('/api/v1/provas', $cabecalho)->assertStatus(403);
        $this->getJson('/api/v1/auth/me', $cabecalho)->assertOk();

        $this->putJson('/api/v1/auth/me', [
            'senha_atual' => 'senha-antiga', 'password' => 'senha-antiga', 'password_confirmation' => 'senha-antiga',
        ], $cabecalho)->assertStatus(422);
        $this->assertTrue($usuario->fresh()->forcar_troca_senha);

        $this->putJson('/api/v1/auth/me', [
            'senha_atual' => 'senha-antiga', 'password' => 'senha-nova-1', 'password_confirmation' => 'senha-nova-1',
        ], $cabecalho)->assertOk();

        $this->assertFalse($usuario->fresh()->forcar_troca_senha);
        $this->app['auth']->forgetGuards();
        $this->assertNotSame(403, $this->getJson('/api/v1/provas', $cabecalho)->status());
    }

    public function test_sessao_aberta_antes_da_marcacao_segue_valendo(): void
    {
        $usuario = $this->usuario('professor', 'c@gabarito360.dev');
        $token = $this->entrar('c@gabarito360.dev')->json('data.token');
        $usuario->update(['forcar_troca_senha' => true]);
        $this->app['auth']->forgetGuards();

        $this->assertNotSame(403, $this->getJson('/api/v1/provas', ['Authorization' => "Bearer {$token}"])->status());
    }

    public function test_link_pelo_e_mail_tambem_conclui_a_troca(): void
    {
        Mail::fake();
        $usuario = $this->usuario('professor', 'd@gabarito360.dev', ['forcar_troca_senha' => true]);

        $this->postJson('/api/v1/auth/esqueci-senha', ['email' => $usuario->email])->assertOk();

        $token = null;
        Mail::assertQueued(EmailModelo::class, function (EmailModelo $mail) use (&$token) {
            parse_str((string) parse_url($mail->dados['link'] ?? '', PHP_URL_QUERY), $query);
            $token = $query['token'] ?? null;
            return true;
        });

        $this->postJson('/api/v1/auth/redefinir-senha', [
            'token' => $token, 'email' => $usuario->email,
            'password' => 'senha-nova-1', 'password_confirmation' => 'senha-nova-1',
        ])->assertOk();

        $this->assertFalse($usuario->fresh()->forcar_troca_senha);
    }
}
