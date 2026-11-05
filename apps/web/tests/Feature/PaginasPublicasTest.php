<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaginasPublicasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url' => 'http://api.test/api']);
        Http::preventStrayRequests();
    }

    public function test_home_e_planos_apresentam_modalidades_sem_precos_ou_compra(): void
    {
        Http::fake(['*' => Http::response(['data' => [[
            'id'=>15, 'nome'=>'Plano comercial que não deve aparecer', 'preco_fixo'=>120,
        ]]])]);
        foreach (['/','/planos'] as $pagina) {
            $this->get($pagina)->assertOk()->assertSee('Modalidades de atendimento')
                ->assertSee('Professor autônomo')->assertSee('Escolas')
                ->assertSee('Núcleos e redes')->assertSee('Secretarias')
                ->assertSee('Disponível no sistema')->assertSee('Previsto no planejamento')
                ->assertSee('ainda serão desenvolvidos')->assertSee('Editor de enunciados')
                ->assertSee(route('leads.index'),false)->assertDontSee('R$')
                ->assertDontSee('Plano comercial que não deve aparecer')->assertDontSee('/checkout')
                ->assertDontSee('licenças')->assertDontSee('cobrança');
        }
        Http::assertNothingSent();
    }

    public function test_apresentacao_funciona_sem_api_e_ajudas_iniciam_recolhidas(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        foreach (['/','/planos'] as $pagina) {
            $resposta=$this->get($pagina)->assertOk()->assertSee('Os planos e as condições de atendimento serão definidos posteriormente.');
            $dom=new \DOMDocument();@$dom->loadHTML($resposta->getContent());$xpath=new \DOMXPath($dom);
            $this->assertSame(4,$xpath->query('//article[contains(@class,"plan-card")]')->length);
            $this->assertSame(0,$xpath->query('//details[@open]')->length);
            $this->assertSame(2,$xpath->query('//section[@aria-label="Recursos do Gabarito360"]//details')->length);
        }
        Http::assertNothingSent();
    }

    public function test_layout_publico_tem_descricao_e_marca_leva_ao_inicio(): void
    {
        Http::fake(['api.test/api/v1/planos' => Http::response(['data' => []])]);
        $this->get('/planos')->assertOk()->assertSee('name="description"', false)
            ->assertSee('href="' . route('home') . '"', false)->assertSee('Pular para o conteúdo');
    }

    public function test_confirmacao_e_autenticacao_nao_sao_indexadas(): void
    {
        $this->get('/fale-conosco/obrigado')->assertOk()->assertSee('noindex, nofollow');
        $this->get('/login')->assertOk()->assertSee('noindex, nofollow');
    }

    public function test_minutas_juridicas_nao_tem_rota_publica(): void
    {
        $this->get('/termos')->assertNotFound();
        $this->get('/privacidade')->assertNotFound();
    }

    public function test_checkout_redireciona_ao_contato_sem_chamar_pagamentos(): void
    {
        $this->get('/checkout')->assertRedirect(route('leads.index'));
        $this->withSession(['auth_token' => 'ficticio'])->get('/checkout?plano_id=15')
            ->assertRedirect(route('leads.index', ['plano_id' => 15]));
        $this->post('/checkout', ['plano_id' => 15, 'licencas' => 2])
            ->assertRedirect(route('leads.index', ['plano_id' => 15]));
        Http::assertNothingSent();
    }

    public function test_contato_preserva_interesse_em_plano_individual(): void
    {
        Http::fake(['api.test/api/v1/planos' => Http::response(['data' => [[
            'id' => 15, 'nome' => 'Individual de teste', 'modo' => 'autoatendimento',
        ]]])]);
        $this->get('/fale-conosco?plano_id=15')->assertOk()->assertSee('Individual de teste')
            ->assertSee('name="plano_id" value="15"', false);
    }
}
