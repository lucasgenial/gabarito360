<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CredenciaisNaUrlTest extends TestCase
{
    public function test_web_nao_encaminha_credencial_na_query_para_api(): void
    {
        Http::preventStrayRequests();
        foreach (['token','access_token','api_token','password','password_confirmation','senha','senha_atual','senha_confirmation','current_password','_token','dados[token]','TOKEN'] as $campo) {
            $this->postJson('/login?'.http_build_query([$campo=>'segredo-ficticio']), ['email'=>'ficticio@example.test','senha'=>'senha-no-corpo'])
                ->assertStatus(400)->assertDontSee('segredo-ficticio')->assertHeader('Referrer-Policy','no-referrer');
        }
        Http::assertNothingSent();
    }

    public function test_link_antigo_de_email_nao_efetiva_acao_nem_reflete_token(): void
    {
        Http::preventStrayRequests();
        $this->get('/confirmar-email?token=segredo-ficticio')->assertStatus(400)->assertDontSee('segredo-ficticio');
        Http::assertNothingSent();
    }
}
