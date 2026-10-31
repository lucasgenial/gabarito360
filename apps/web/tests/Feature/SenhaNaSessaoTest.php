<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SenhaNaSessaoTest extends TestCase
{
    public function test_validacao_nao_guarda_as_senhas_na_sessao(): void
    {
        Http::preventStrayRequests();

        $response = $this->withSession([
            'auth_token' => 'token-ficticio',
            'auth_trocar_senha' => true,
        ])->from('/trocar-senha')->put('/trocar-senha', [
            'senha_atual' => 'senha-antiga-ficticia',
            'password' => 'senha-nova-ficticia',
            'password_confirmation' => 'confirmacao-diferente',
        ]);

        $response->assertRedirect('/trocar-senha')
            ->assertSessionHasErrors('password')
            ->assertSessionHas('auth_trocar_senha', true);

        foreach (['senha_atual', 'password', 'password_confirmation'] as $campo) {
            $this->assertArrayNotHasKey($campo, session()->getOldInput());
        }

        Http::assertNothingSent();
    }
}
