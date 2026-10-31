<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinksProtegidosTest extends TestCase
{
    public function test_recuperacao_aceita_conta_opaca_e_preserva_cabecalhos_privados(): void
    {
        Http::preventStrayRequests();
        $conta = str_repeat('a', 64);
        $this->get('/redefinir-senha?token=token-ficticio&conta='.$conta)->assertOk()
            ->assertSee('value="'.$conta.'"', false)
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cache-Control', 'no-store, private');
        Http::fake(['*' => Http::response(['data' => ['mensagem' => 'Senha salva']], 200)]);
        $this->post('/redefinir-senha', ['email' => $conta, 'token' => 'token-ficticio',
            'password' => 'senha-ficticia', 'password_confirmation' => 'senha-ficticia'])->assertRedirect('/login');
        Http::assertSent(fn ($r) => $r['email'] === $conta && $r['token'] === 'token-ficticio');
    }

    public function test_convite_opaco_e_link_legado_continuam_aceitos(): void
    {
        Http::fake(['*' => Http::response(['data' => ['nome' => 'Pessoa fictícia', 'convidado_por' => 'Gestor fictício',
            'funcao' => 'Professor', 'tipo_vinculo' => 'Escola', 'vinculo' => 'Escola fictícia', 'turmas' => '3A']], 200)]);
        $conta = str_repeat('b', 64);
        $this->get('/convite?token=ficticio&conta='.$conta)->assertOk()->assertSee('value="'.$conta.'"', false);
        Http::assertSent(fn ($r) => $r['email'] === $conta);
        $this->get('/redefinir-senha?token=ficticio&email=pessoa%40example.test')->assertOk()
            ->assertSee('value="pessoa@example.test"', false);
    }
}
