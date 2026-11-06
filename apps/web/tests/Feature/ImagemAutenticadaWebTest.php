<?php

namespace Tests\Feature;

use App\Services\ApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Support\NavegacaoHttp;

class ImagemAutenticadaWebTest extends TestCase
{
    use NavegacaoHttp;
    use \Tests\Support\AcessoAtivoLegado;
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
    }

    public function test_api_references_become_web_resources_without_credentials(): void
    {
        $this->withSession(['auth_token'=>'segredo-ficticio-apenas-no-cabecalho']);
        $this->apiLegada(['api.test/api/v1/provas/1/cartoes'=>Http::response(['data'=>['cartoes'=>[
            ['id'=>7,'foto_url'=>'/api/v1/cartoes/7/imagens/original','miniatura_url'=>'/api/v1/cartoes/7/imagens/miniatura'],
        ]]])]);
        $r=app(ApiClient::class)->get('/v1/provas/1/cartoes');
        $this->assertSame(route('cartoes.imagem-segura',['id'=>7,'tipo'=>'original']), $r->json('data.cartoes.0.foto_url'));
        $this->assertNull(parse_url($r->json('data.cartoes.0.foto_url'),PHP_URL_QUERY));
        $this->assertStringNotContainsString('segredo-ficticio', $r->body());
        Http::assertSent(fn($q)=>$q->hasHeader('Authorization','Bearer segredo-ficticio-apenas-no-cabecalho'));
    }

    public function test_incompatible_signed_reference_is_not_returned_to_browser(): void
    {
        $this->apiLegada(['api.test/*'=>Http::response(['data'=>['cartao_foto_url'=>'/api/v1/cartoes/7/arquivo/original?signature=capacidade-antiga']])]);
        $r=app(ApiClient::class)->get('/v1/resultado');
        $this->assertNull($r->json('data.cartao_foto_url'));
        $this->assertStringNotContainsString('capacidade-antiga', $r->body());
    }

    public function test_web_guest_cannot_proxy_image(): void
    {
        $this->get(route('cartoes.imagem-segura',['id'=>7,'tipo'=>'original']))->assertRedirect(route('login'));
        Http::assertNothingSent();
    }

    public function test_proxy_uses_bearer_and_private_image_response(): void
    {
        $this->selecionarAcessoLegado('professor', ['correcao.acompanhar'], 'token-ficticio-servidor');
        $this->apiLegada(['api.test/api/v1/cartoes/7/imagens/original'=>Http::response('bytes-ficticios',200,['Content-Type'=>'image/jpeg'])]);
        $this->consultarTela(route('cartoes.imagem-segura',['id'=>7,'tipo'=>'original']))->assertOk()
            ->assertHeader('Content-Type','image/jpeg')->assertHeader('Cache-Control','no-store, private')
            ->assertHeader('X-Content-Type-Options','nosniff')->assertContent('bytes-ficticios');
        Http::assertSent(fn($q)=>$q->url()==='http://api.test/api/v1/cartoes/7/imagens/original' && $q->hasHeader('Authorization','Bearer token-ficticio-servidor'));
    }

    public function test_proxy_does_not_render_html_or_pass_upstream_redirect(): void
    {
        $this->selecionarAcessoLegado('professor', ['correcao.acompanhar'], 'token-ficticio');
        $this->apiLegada(['api.test/*'=>Http::response('<html>conteudo indevido</html>',200,['Content-Type'=>'text/html'])]);
        $this->consultarTela(route('cartoes.imagem-segura',['id'=>7,'tipo'=>'original']))->assertStatus(502)->assertDontSee('conteudo indevido');
    }
}
