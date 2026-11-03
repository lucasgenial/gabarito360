<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResultadosLoteWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'ficticio']);
    }

    public function test_download_encaminha_turma_e_entrega_pdf_privado(): void
    {
        Http::fake(['*'=>Http::response('%PDF-ficticio',200,['Content-Type'=>'application/pdf'])]);
        $this->get('/provas/7/resultados/pdf?turma_id=3')->assertOk()
            ->assertHeader('Content-Type','application/pdf')
            ->assertHeader('Content-Disposition','attachment; filename="resultados-prova-7-turma-3.pdf"');
        Http::assertSent(fn($r)=>str_contains($r->url(),'/api/v1/provas/7/resultados/pdf?turma_id=3') && $r->hasHeader('Authorization','Bearer ficticio'));
    }

    public function test_erro_da_api_nao_vira_pdf_e_filtro_invalido_nao_faz_requisicao(): void
    {
        Http::fake(['*'=>Http::response(['message'=>'Nenhum resultado disponível.'],422)]);
        $this->getJson('/provas/7/resultados/pdf')->assertUnprocessable();
        $this->getJson('/provas/7/resultados/pdf?turma_id=abc')->assertUnprocessable();
        Http::assertSentCount(1);
    }
}
