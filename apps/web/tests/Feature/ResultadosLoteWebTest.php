<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Support\NavegacaoHttp;
use Tests\Support\AcessoAtivoLegado;

class ResultadosLoteWebTest extends TestCase
{
    use NavegacaoHttp;
    use AcessoAtivoLegado;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'ficticio']);
        $this->selecionarAcessoLegado('coordenador', ['resultado.ver', 'prova.ver']);
    }

    public function test_download_encaminha_turma_e_entrega_pdf_privado(): void
    {
        $this->apiLegada(['*'=>Http::response('%PDF-ficticio',200,['Content-Type'=>'application/pdf'])]);
        $this->consultarTela('/app/provas/7/resultados/pdf?turma_id=3')->assertOk()
            ->assertHeader('Content-Type','application/pdf')
            ->assertHeader('Content-Disposition','attachment; filename="resultados-prova-7-turma-3.pdf"');
        Http::assertSent(fn($r)=>str_contains($r->url(),'/api/v1/provas/7/resultados/pdf?turma_id=3') && $r->hasHeader('Authorization','Bearer ficticio'));
    }

    public function test_erro_da_api_nao_vira_pdf_e_filtro_invalido_nao_faz_requisicao(): void
    {
        $this->apiLegada(['*'=>Http::response(['message'=>'Nenhum resultado disponível.'],422)]);
        $this->consultarJson('/app/provas/7/resultados/pdf')->assertUnprocessable();
        $this->consultarJson('/app/provas/7/resultados/pdf?turma_id=abc')->assertUnprocessable();
        $this->assertCount(1, Http::recorded(fn ($r) => parse_url($r->url(), PHP_URL_PATH) !== '/api/v1/auth/me'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'turma_id=abc'));
    }
}
