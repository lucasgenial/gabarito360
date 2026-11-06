<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinksProtegidosTest extends TestCase
{
    public function test_gets_com_segredos_legados_sao_limpos_sem_consulta_nem_acao(): void
    {
        Http::preventStrayRequests();
        foreach (['/redefinir-senha', '/convite', '/confirmar-email', '/app/vinculos/convite'] as $url) {
            $this->get($url.'?token='.str_repeat('a',64).'&email=pessoa%40example.test')
                ->assertStatus(400);
        }
        Http::assertNothingSent();
    }

    public function test_formularios_fixos_nao_consomem_codigo_no_get(): void
    {
        Http::preventStrayRequests();
        foreach (['/redefinir-senha', '/convite', '/confirmar-email'] as $url) {
            $this->get($url)->assertOk()->assertSee('name="token"', false)
                ->assertHeader('Referrer-Policy', 'no-referrer')
                ->assertHeader('Cache-Control', 'no-store, private');
        }
        Http::assertNothingSent();
    }

    public function test_redefinicao_envia_codigo_somente_no_corpo(): void
    {
        $token = str_repeat('b',64);
        Http::fake(['*'=>Http::response(['data'=>['mensagem'=>'Senha salva']],200)]);
        $this->post('/redefinir-senha', ['email'=>'pessoa@example.test','token'=>$token,
            'password'=>'senha-ficticia','password_confirmation'=>'senha-ficticia'])->assertRedirect('/login');
        Http::assertSent(fn($r)=>$r->method()==='POST' && !str_contains($r->url(),$token)
            && $r['email']==='pessoa@example.test' && $r['token']===$token);
    }

    public function test_confirmacao_exige_post_com_codigo_e_nao_altera_email_no_get(): void
    {
        $token = str_repeat('c',64);
        Http::fake(['*'=>Http::response(['data'=>['mensagem'=>'Confirmado']],200)]);
        $this->get('/confirmar-email')->assertOk();
        Http::assertNothingSent();
        $this->post('/confirmar-email',['token'=>$token])->assertRedirect('/login');
        Http::assertSent(fn($r)=>$r->method()==='POST' && str_ends_with($r->url(),'/auth/confirmar-email') && $r['token']===$token);
    }

    public function test_credencial_na_query_e_rejeitada_mesmo_com_corpo_valido(): void
    {
        Http::preventStrayRequests();
        $token = str_repeat('d',64);
        foreach (['/redefinir-senha','/confirmar-email','/convite/conferir'] as $url) {
            $this->postJson($url.'?token='.$token,['email'=>'pessoa@example.test','token'=>$token,
                'password'=>'senha-ficticia','password_confirmation'=>'senha-ficticia'])->assertStatus(400);
        }
        Http::assertNothingSent();
    }
}
