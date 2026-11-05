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
                ->assertSee('Em breve')->assertSee('Elaboração de provas e avaliações')
                ->assertSee('A contratação será disponibilizada no lançamento.')
                ->assertDontSee('Previsto no planejamento')->assertDontSee('Roadmap')->assertDontSee('Em desenvolvimento')
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
            $resposta=$this->get($pagina)->assertOk()->assertSee('A contratação será disponibilizada no lançamento.');
            $dom=new \DOMDocument();@$dom->loadHTML($resposta->getContent());$xpath=new \DOMXPath($dom);
            $this->assertSame(4,$xpath->query('//article[contains(@class,"plan-card")]')->length);
            $this->assertSame(0,$xpath->query('//details[@open]')->length);
            $futuros=$xpath->query('//*[@data-recurso-futuro]');
            $this->assertSame(7,$futuros->length);
            foreach ($futuros as $futuro) {
                $this->assertSame(1,$xpath->query('.//span[contains(@class,"publico-em-breve") and text()="Em breve"]',$futuro)->length);
            }
            $this->assertSame(9,$xpath->query('//div[contains(@class,"publico-faq")]/details')->length);
            $this->assertSame(1,$xpath->query('//details[@data-menu-publico]/summary')->length);
            $this->assertSame(0,$xpath->query('//a[not(@href) or @href="" or @href="#"]')->length);
            $this->assertSame(4,$xpath->query('//article[contains(@class,"plan-card")]/'.($pagina==='/'?'h3':'h2'))->length);
        }
        Http::assertNothingSent();
    }

    public function test_home_demonstra_fluxos_reais_com_dados_ficticios_sem_formularios_ou_promessas_numericas(): void
    {
        $resposta=$this->get('/')->assertOk()->assertSee('pré-lançamento')
            ->assertSee('Revisar leitura')->assertSee('Questão por questão')->assertSee('Relatório individual')
            ->assertSee('Dados fictícios')->assertDontSee('TALIS')->assertDontSee('15/min')
            ->assertSee('css/apresentacao-publica.css',false)->assertSee('js/apresentacao-publica.js',false);
        $dom=new \DOMDocument();@$dom->loadHTML($resposta->getContent());$xpath=new \DOMXPath($dom);
        $this->assertSame(4,$xpath->query('//article[contains(@class,"publico-demo")]/figure/figcaption')->length);
        $this->assertSame(0,$xpath->query('//form')->length);
        $this->assertSame(1,$xpath->query('//main[@id="conteudo"]')->length);
        foreach (['como-funciona','recursos','demonstracoes','planos','duvidas'] as $id) {
            $this->assertSame(1,$xpath->query('//*[@id="'.$id.'"]')->length);
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
