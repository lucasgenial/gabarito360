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

    public function test_planos_usam_os_precos_e_limites_da_api(): void
    {
        Http::fake(['api.test/api/v1/planos' => Http::response(['data' => [[
            'id' => 15, 'nome' => 'Plano de teste', 'publico' => 'individual', 'modo' => 'autoatendimento',
            'preco_fixo' => 120, 'preco_por_licenca' => null, 'periodicidade' => 'anual',
            'licencas_min' => 1, 'licencas_max' => 1, 'limites' => ['turmas' => 7],
        ]]])]);
        $this->get('/planos')->assertOk()->assertSee('R$ 120,00')->assertSee('Até 7 turma(s)')
            ->assertSee(route('leads.index'), false)->assertDontSee('Economia de 16%')->assertDontSee('90 dias');
    }

    public function test_indisponibilidade_da_api_nao_inventa_precos(): void
    {
        Http::fake(['api.test/api/v1/planos' => Http::failedConnection()]);
        $this->get('/planos')->assertOk()->assertSee('Não foi possível consultar os preços agora.')
            ->assertDontSee('R$ 9,99');
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
}
