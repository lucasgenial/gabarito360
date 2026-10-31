<?php

namespace Tests\Feature;

use App\Mail\EmailModelo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Arquivo: apps/api/tests/Feature/EmailsTest.php
 * Função: Motor de e-mail, recuperação de senha e Fale Conosco.
 */
class EmailsTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): Usuario
    {
        return Usuario::create([
            'perfil' => 'professor', 'nome' => 'Prof. Teste', 'email' => 'prof@gabarito360.dev',
            'cpf' => '00000000099', 'password' => Hash::make('senha-antiga'), 'ativo' => true,
        ]);
    }

    public function test_modelo_escapa_valores_e_remove_campos_vazios(): void
    {
        $html = (new EmailModelo('recuperar-senha', 'Assunto', ['nome' => '<b>Ana</b>', 'link' => 'https://x.dev/?a=1&b=2']))->renderizar();

        $this->assertStringContainsString('&lt;b&gt;Ana&lt;/b&gt;', $html);
        $this->assertStringContainsString('a=1&amp;b=2', $html);
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*[a-z_]+\s*\}\}/', $html);
    }

    public function test_recuperacao_envia_link_pela_fila(): void
    {
        Mail::fake();
        $this->usuario();

        $this->postJson('/api/v1/auth/esqueci-senha', ['email' => 'prof@gabarito360.dev'])->assertOk();

        Mail::assertQueued(EmailModelo::class, fn ($m) => $m->modelo === 'recuperar-senha'
            && str_contains((string) $m->dados['link'], '/redefinir-senha?token='));
    }

    public function test_email_desconhecido_recebe_a_mesma_resposta_e_nada_e_enviado(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/esqueci-senha', ['email' => 'ninguem@gabarito360.dev'])->assertOk();

        Mail::assertNothingQueued();
    }

    private function redefinir(string $token): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/auth/redefinir-senha', [
            'email' => 'prof@gabarito360.dev', 'token' => $token,
            'password' => 'senha-nova-123', 'password_confirmation' => 'senha-nova-123',
        ]);
    }

    public function test_token_so_vale_uma_vez_e_avisa_a_troca(): void
    {
        Mail::fake();
        $usuario = $this->usuario();
        $this->postJson('/api/v1/auth/esqueci-senha', ['email' => $usuario->email]);
        $token = null;
        Mail::assertQueued(EmailModelo::class, function ($m) use (&$token) {
            parse_str((string) parse_url((string) $m->dados['link'], PHP_URL_QUERY), $q);
            $token = $q['token'];
            return true;
        });

        $this->redefinir($token)->assertOk();
        $this->redefinir($token)->assertStatus(422);

        $this->assertTrue(Hash::check('senha-nova-123', $usuario->fresh()->password));
        Mail::assertQueued(EmailModelo::class, fn ($m) => $m->modelo === 'senha-alterada');
    }

    public function test_token_com_mais_de_60_minutos_e_recusado(): void
    {
        $this->usuario();
        DB::table('password_reset_tokens')->insert([
            'email' => 'prof@gabarito360.dev', 'token' => Hash::make('abc'), 'created_at' => now()->subMinutes(61),
        ]);

        $this->redefinir('abc')->assertStatus(422);
    }

    public function test_fale_conosco_avisa_a_equipe_e_confirma_a_quem_escreveu(): void
    {
        Mail::fake();
        config(['emails.contato' => 'contatos@gabarito360.dev']);

        $this->postJson('/api/v1/leads', [
            'nome' => 'Ana', 'email' => 'ana@escola.dev', 'assunto' => 'Conhecer o sistema', 'mensagem' => 'Olá',
        ])->assertSuccessful();

        Mail::assertQueued(EmailModelo::class, fn ($m) => $m->modelo === 'fale-conosco-equipe'
            && $m->hasTo('contatos@gabarito360.dev') && $m->responderPara === 'ana@escola.dev');
        Mail::assertQueued(EmailModelo::class, fn ($m) => $m->modelo === 'fale-conosco-confirmacao' && $m->hasTo('ana@escola.dev'));
    }

    public function test_convite_cria_a_senha_e_vale_mais_que_o_link_comum(): void
    {
        Mail::fake();
        $usuario = $this->usuario();
        app(\App\Services\ContaUsuario::class)->convidar($usuario);

        $token = null;
        Mail::assertQueued(EmailModelo::class, function ($m) use (&$token) {
            parse_str((string) parse_url((string) $m->dados['link'], PHP_URL_QUERY), $q);
            $token = $q['token'];
            return $m->modelo === 'convite-usuario';
        });

        $this->travel(6)->days();
        $this->redefinir($token)->assertOk();
        Mail::assertNotQueued(EmailModelo::class, fn ($m) => $m->modelo === 'senha-alterada');
    }

    public function test_convite_vencido_e_recusado(): void
    {
        Mail::fake();
        $usuario = $this->usuario();
        DB::table('password_reset_tokens')->insert([
            'email' => $usuario->email, 'token' => Hash::make('abc'), 'created_at' => now()->subDays(8), 'expira_em' => now()->subDay(),
        ]);

        $this->redefinir('abc')->assertStatus(422);
    }

    private function pedirTroca(Usuario $usuario): string
    {
        \Laravel\Sanctum\Sanctum::actingAs($usuario);
        $this->postJson('/api/v1/auth/email', ['novo_email' => 'novo@gabarito360.dev', 'senha_atual' => 'senha-antiga'])->assertOk();

        $token = null;
        Mail::assertQueued(EmailModelo::class, function ($m) use (&$token) {
            if ($m->modelo === 'confirmar-email') {
                parse_str((string) parse_url((string) $m->dados['link'], PHP_URL_QUERY), $q);
                $token = $q['token'];
            }
            return true;
        });

        return $token;
    }

    public function test_email_so_muda_depois_de_confirmar_e_avisa_o_anterior(): void
    {
        Mail::fake();
        $usuario = $this->usuario();
        $token = $this->pedirTroca($usuario);

        $this->assertSame('prof@gabarito360.dev', $usuario->fresh()->email);

        $this->postJson('/api/v1/auth/confirmar-email', ['token' => $token])->assertOk();
        $this->assertSame('novo@gabarito360.dev', $usuario->fresh()->email);
        Mail::assertQueued(EmailModelo::class, fn ($m) => $m->modelo === 'email-alterado-aviso' && $m->hasTo('prof@gabarito360.dev'));

        $this->postJson('/api/v1/auth/confirmar-email', ['token' => $token])->assertStatus(422);
    }

    public function test_troca_exige_a_senha_atual(): void
    {
        Mail::fake();
        \Laravel\Sanctum\Sanctum::actingAs($this->usuario());

        $this->postJson('/api/v1/auth/email', ['novo_email' => 'novo@gabarito360.dev', 'senha_atual' => 'errada'])->assertStatus(422);
        Mail::assertNothingQueued();
    }

    public function test_link_de_troca_vencido_e_recusado(): void
    {
        Mail::fake();
        $usuario = $this->usuario();
        $token = $this->pedirTroca($usuario);

        $this->travel(25)->hours();

        $this->postJson('/api/v1/auth/confirmar-email', ['token' => $token])->assertStatus(422);
        $this->assertSame('prof@gabarito360.dev', $usuario->fresh()->email);
    }
}
