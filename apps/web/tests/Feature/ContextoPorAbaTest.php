<?php
namespace Tests\Feature;

use App\Services\ApiClient;
use App\Support\Acesso;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ContextoPorAbaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url' => 'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token' => 'identidade', 'auth_vinculos' => true, 'auth_perfil' => null,
            'auth_permissoes' => [], 'auth_nome' => 'Pessoa fictícia']);
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->withCredentials();
        Route::middleware(['web', 'autenticado'])->get('/teste-contexto', function () {
            return response()->json(['perfil' => Acesso::perfil(), 'permissoes' => Acesso::permissoes()]);
        });
        Route::middleware(['web', 'autenticado'])->post('/teste-contexto', function () {
            app(ApiClient::class)->post('/v1/operacao');
            return redirect('/teste-contexto');
        });
        Route::middleware(['web', 'autenticado'])->get('/teste-pdf', function () {
            $r = app(ApiClient::class)->getArquivo('/v1/arquivo');
            return response($r->body())->header('Content-Type', 'application/pdf');
        });
    }

    private function fingir($callback): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake($callback);
    }

    private function dados(string $token, string $perfil = 'professor'): array
    {
        return ['token' => $token, 'perfil' => $perfil, 'permissoes' => ['prova.ver'], 'espaco' => ['id' => 1, 'nome' => 'Escola fictícia', 'tipo' => 'escola']];
    }

    public function test_dois_contextos_usam_tokens_e_permissoes_independentes_sem_mudar_sessao(): void
    {
        $a = ContextoWeb::criar($this->dados('token-a', 'dir_escolar'));
        $b = ContextoWeb::criar($this->dados('token-b'));
        $this->fingir(fn ($r) => Http::response(['data' => $this->dados('omitido', $r->hasHeader('Authorization', 'Bearer token-a') ? 'dir_escolar' : 'professor')]));
        $this->getJson('/teste-contexto?_ctx='.$a)->assertOk()->assertJsonPath('perfil', 'dir_escolar');
        $this->getJson('/teste-contexto?_ctx='.$b)->assertOk()->assertJsonPath('perfil', 'professor');
        $this->getJson('/teste-contexto?_ctx='.$a)->assertOk()->assertJsonPath('perfil', 'dir_escolar');
        $this->assertSame('identidade', session('auth_token'));
        $this->assertNull(session('auth_perfil'));
        $this->assertSame([], session('auth_permissoes'));
        Http::assertSentCount(3);
    }

    public function test_contexto_ausente_invalido_de_outra_sessao_e_expirado_nao_envia_operacao(): void
    {
        $this->get('/teste-contexto')->assertOk()->assertSee('Abrindo seu vínculo');
        $this->postJson('/teste-contexto')->assertStatus(409);
        $this->postJson('/teste-contexto', ['_ctx' => 'inventado'])->assertStatus(409);
        $id = ContextoWeb::criar($this->dados('privado'));
        $this->withSession(['auth_token' => 'outra-identidade']); session()->save();
        $this->postJson('/teste-contexto', ['_ctx' => $id])->assertStatus(409);
        $this->withSession(['auth_token' => 'identidade']); session()->save();
        $this->travel(121)->minutes();
        $this->postJson('/teste-contexto', ['_ctx' => $id])->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_revogacao_nao_reexecuta_post_e_falha_api_nao_apaga_contexto(): void
    {
        $id = ContextoWeb::criar($this->dados('revogado'));
        $this->fingir(['*' => Http::response(['message' => 'Indisponível'], 403)]);
        $this->postJson('/teste-contexto', ['_ctx' => $id])->assertStatus(409)->assertJsonPath('selecionar_vinculo', true);
        $this->assertNull(ContextoWeb::obter($id));
        Http::assertSentCount(1);
        $id2 = ContextoWeb::criar($this->dados('temporario'));
        $this->fingir(['*' => Http::response([], 503)]);
        $this->getJson('/teste-contexto?_ctx='.$id2)->assertStatus(503);
        $this->assertNotNull(ContextoWeb::obter($id2));
    }

    public function test_form_ajax_download_e_redirect_preservam_token_da_aba(): void
    {
        $id = ContextoWeb::criar($this->dados('token-pdf'));
        $this->fingir(function ($r) {
            $this->assertTrue($r->hasHeader('Authorization', 'Bearer token-pdf'));
            if (str_ends_with($r->url(), '/arquivo')) return Http::response('%PDF-ficticio', 200, ['Content-Type' => 'application/pdf']);
            return Http::response(['data' => $this->dados('omitido')]);
        });
        $this->post('/teste-contexto', ['_ctx' => $id])->assertRedirect('/teste-contexto?_ctx='.$id);
        $this->getJson('/teste-contexto', ['X-G360-Contexto' => $id])->assertOk();
        $this->get('/teste-pdf?_ctx='.$id)->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertSee('%PDF-ficticio');
    }

    public function test_escolha_varios_unico_e_sem_vinculo_e_token_nunca_aparece_no_html(): void
    {
        $item = ['id' => 1, 'perfil' => 'professor', 'espaco' => ['nome' => 'Escola fictícia']];
        $this->fingir(['*' => Http::response(['data' => [$item, array_merge($item, ['id' => 2])]])]);
        $this->get('/vinculos')->assertOk()->assertSee('Escolha seu vínculo')->assertDontSee('id="vinculo-unico"', false);
        $this->fingir(['*' => Http::response(['data' => [$item]])]);
        $this->get('/vinculos')->assertOk()->assertSee('id="vinculo-unico"', false);
        $this->get('/vinculos?trocar=1')->assertOk()->assertDontSee('id="vinculo-unico"', false);
        $this->fingir(['*' => Http::response(['data' => []])]);
        $this->get('/vinculos')->assertOk()->assertSee('Nenhum vínculo disponível');
        $this->fingir(['*' => Http::response(['data' => $this->dados('segredo-nao-publicar')], 201)]);
        $r = $this->post('/vinculos', ['vinculo_id' => 1])->assertRedirect();
        $this->assertStringNotContainsString('segredo-nao-publicar', $r->headers->get('Location'));
        parse_str(parse_url($r->headers->get('Location'), PHP_URL_QUERY), $q);
        $this->assertSame('segredo-nao-publicar', ContextoWeb::obter($q['_ctx'])['token']);
        $this->assertSame('identidade', session('auth_token'));
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer identidade'));
    }

    public function test_menu_e_topo_usam_contexto_e_logout_usa_identidade(): void
    {
        $id = ContextoWeb::criar($this->dados('contextual'));
        $this->fingir(['*' => Http::response(['data' => $this->dados('omitido')])]);
        $this->get('/referencia-visual?_ctx='.$id)->assertOk()->assertSee('Trocar vínculo')->assertSee('Escola fictícia')->assertDontSee('contextual');
        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('auth_token');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/auth/logout') && $r->hasHeader('Authorization', 'Bearer identidade'));
    }
}
