<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CartoesFilaTest extends TestCase
{
    private const CHAVE = 'f4951e0c-7470-42f8-8141-683c25514b7e';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url' => 'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token' => 'ficticio']);
    }

    public function test_upload_preserva_202_e_identificador_sem_aguardar_resultado(): void
    {
        Http::fake(['api.test/api/v1/provas/7/cartoes' => Http::response(['data' => [
            'envio_id' => self::CHAVE, 'processamento' => 'na_fila', 'resultado' => [],
        ]], 202)]);
        $this->postJson('/provas/7/cartoes', ['envio_id' => self::CHAVE,
            'imagem' => UploadedFile::fake()->image('cartao.jpg')])->assertStatus(202)
            ->assertJsonPath('envio_id', self::CHAVE)->assertJsonPath('processamento', 'na_fila');
        Http::assertSent(fn ($r) => str_contains($r->body(), self::CHAVE));
    }

    public function test_consulta_e_retentativa_repassam_status_e_resultado_da_api(): void
    {
        $path = '/provas/7/cartoes/envios/'.self::CHAVE;
        Http::fake(['api.test/api/v1'.$path => Http::response(['data' => ['processamento' => 'erro', 'pode_repetir' => true]]),
            'api.test/api/v1'.$path.'/repetir' => Http::response(['data' => ['processamento' => 'na_fila']], 202)]);
        $this->getJson($path)->assertOk()->assertJsonPath('pode_repetir', true);
        $this->postJson($path.'/repetir')->assertStatus(202)->assertJsonPath('processamento', 'na_fila');
    }

    public function test_consulta_negada_na_api_nao_vira_sucesso_na_web(): void
    {
        $path = '/provas/7/cartoes/envios/'.self::CHAVE;
        Http::fake(['*' => Http::response(['message' => 'Envio não encontrado.'], 404)]);
        $this->getJson($path)->assertNotFound()->assertJsonPath('erro', 'Envio não encontrado.');
    }

    public function test_formulario_manual_leva_recibo_para_acompanhamento_apos_redirect(): void
    {
        Http::fake(['*' => Http::response(['data' => ['envio_id' => self::CHAVE, 'processamento' => 'na_fila']], 202)]);
        $this->from('/provas/7/acompanhar')->post('/provas/7/cartoes', ['envio_id' => self::CHAVE,
            'imagem' => UploadedFile::fake()->image('cartao.jpg')])->assertRedirect('/provas/7/acompanhar')
            ->assertSessionHas('cartao_envio', self::CHAVE);
    }
}
