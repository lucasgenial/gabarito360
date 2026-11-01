<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContextoInstitucionalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url' => 'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token' => 'ficticio', 'auth_perfil' => 'admin_rede', 'auth_nome' => 'Fictício']);
    }

    public function test_duas_redes_exigem_escolha_e_formulario_preserva_destino(): void
    {
        Http::fake(function ($r) {
            if (isset($r['listar_redes'])) return Http::response(['data' => ['redes' => [['id' => 1, 'nome' => 'Rede A'], ['id' => 2, 'nome' => 'Rede B']]]]);
            return Http::response(['data' => ['rede_id' => 2, 'nome' => 'Rede B', 'meta_media' => 7, 'meta_minima' => 6, 'limiar_seges_min' => 30]]);
        });
        $this->get('/configuracoes')->assertOk()->assertSee('Selecione a rede')->assertDontSee('Salvar configurações');
        $this->get('/configuracoes?rede_id=2')->assertOk()->assertSee('name="rede_id" value="2"', false);
        $this->post('/configuracoes', ['_method' => 'PUT', 'rede_id' => 2, 'meta_media' => 8, 'meta_minima' => 6, 'limiar_seges_min' => 30])
            ->assertRedirect('/configuracoes?rede_id=2');
        Http::assertSent(fn ($r) => $r->method() === 'PUT' && (int) $r['rede_id'] === 2);
        $this->assertNull(session('rede_id'));
    }

    public function test_destino_revogado_na_api_nao_exibe_configuracao_nem_salva(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Não encontrado.'], 404)]);
        $this->get('/configuracoes?rede_id=2')->assertUnprocessable();
        $this->from('/configuracoes?rede_id=2')->put('/configuracoes', ['rede_id' => 2, 'meta_media' => 8, 'meta_minima' => 6, 'limiar_seges_min' => 30])
            ->assertRedirect('/configuracoes?rede_id=2')->assertSessionHas('erro');
    }

    public function test_painel_solicita_escolha_e_envia_id_da_url_sem_estado_global(): void
    {
        $this->withSession(['auth_perfil' => 'dir_escolar']);
        Http::fake(fn ($r) => Http::response(['data' => ['selecao' => ['campo' => 'escola_id', 'opcoes' => [['id' => 1, 'nome' => 'Escola A'], ['id' => 2, 'nome' => 'Escola B']]]]], 422));
        $this->get(route('painel'))->assertOk()->assertSee('Selecionar contexto')->assertSee('name="escola_id"', false);
        $this->get(route('painel', ['escola_id' => 2]))->assertOk();
        Http::assertSent(fn ($r) => (int) ($r['escola_id'] ?? 0) === 2);
        $this->assertNull(session('escola_id'));
    }
}
