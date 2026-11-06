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
        $this->withHeader('X-G360-Revisao', ContextoWeb::garantirRevisao());
        Route::middleware(['web', 'autenticado'])->get('/app/teste-contexto', function () {
            return response()->json(['perfil' => Acesso::perfil(), 'permissoes' => Acesso::permissoes()]);
        });
        Route::middleware(['web', 'autenticado'])->post('/app/teste-contexto', function () {
            app(ApiClient::class)->post('/v1/operacao');
            return redirect('/teste-contexto');
        });
        Route::middleware(['web', 'autenticado'])->get('/app/teste-pdf', function () {
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
        return ['vinculo_id' => $token === 'token-b' ? 2 : 1, 'token' => $token, 'perfil' => $perfil, 'permissoes' => ['prova.ver'], 'espaco' => ['id' => 1, 'nome' => 'Escola fictícia', 'tipo' => 'escola']];
    }

    private function ativar(array $dados): string
    {
        $revision = ContextoWeb::ativar($dados);
        $this->withHeader('X-G360-Revisao', $revision);
        session()->save();
        return session('auth_contexto_ativo');
    }

    private function url(string $path, string $id): string { return '/app'.$path; }

    public function test_um_contexto_ativo_troca_autoridade_e_recusa_acao_da_tela_anterior(): void
    {
        $this->ativar($this->dados('token-a', 'dir_escolar'));
        $velha = session('auth_contexto_revisao');
        $this->fingir(fn ($r) => Http::response(['data' => $this->dados($r->hasHeader('Authorization', 'Bearer token-a') ? 'token-a' : 'token-b', $r->hasHeader('Authorization', 'Bearer token-a') ? 'dir_escolar' : 'professor')]));
        $this->getJson('/app/teste-contexto')->assertOk()->assertJsonPath('perfil', 'dir_escolar');
        $this->ativar($this->dados('token-b'));
        $nova = session('auth_contexto_revisao');
        $this->withHeader('X-G360-Revisao', $velha)->postJson('/app/teste-contexto')->assertStatus(409);
        Http::assertSentCount(1);
        $this->withHeader('X-G360-Revisao', $nova)->getJson('/app/teste-contexto')->assertOk()->assertJsonPath('perfil', 'professor');
        $this->assertSame('identidade', session('auth_token'));
        $this->assertNull(session('auth_perfil'));
        $this->assertSame([], session('auth_permissoes'));
        Http::assertSentCount(2);
    }

    public function test_contexto_ausente_invalido_de_outra_sessao_e_expirado_nao_envia_operacao(): void
    {
        $this->get('/app/painel')->assertRedirect('/app/vinculos');
        $this->postJson('/provas')->assertStatus(409);
        $this->postJson('/acessos/vinculo-999/turmas')->assertStatus(409);
        $id = $this->ativar($this->dados('privado'));
        $this->withSession(['auth_token' => 'outra-identidade']); session()->save();
        $this->postJson('/app/teste-contexto')->assertStatus(409);
        $this->withSession(['auth_token' => 'identidade']); session()->save();
        $this->travel(121)->minutes();
        $this->postJson('/app/teste-contexto')->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_revogacao_nao_reexecuta_post_e_falha_api_nao_apaga_contexto(): void
    {
        $id = $this->ativar($this->dados('revogado'));
        $this->fingir(['*' => Http::response(['message' => 'Indisponível'], 403)]);
        $this->postJson('/app/teste-contexto')->assertStatus(409)->assertJsonPath('selecionar_vinculo', true);
        $this->assertNull(ContextoWeb::obter($id));
        Http::assertSentCount(1);
        $id2 = $this->ativar($this->dados('temporario'));
        $this->fingir(['*' => Http::response([], 503)]);
        $this->getJson($this->url('/teste-contexto',$id2))->assertStatus(503);
        $this->assertNotNull(ContextoWeb::obter($id2));
    }

    public function test_form_ajax_download_e_redirect_preservam_snapshot_ativo(): void
    {
        $id = $this->ativar($this->dados('token-pdf'));
        $this->fingir(function ($r) {
            $this->assertTrue($r->hasHeader('Authorization', 'Bearer token-pdf'));
            if (str_ends_with($r->url(), '/arquivo')) return Http::response('%PDF-ficticio', 200, ['Content-Type' => 'application/pdf']);
            return Http::response(['data' => $this->dados('omitido')]);
        });
        $r=$this->post($this->url('/teste-contexto',$id))->assertRedirect();
        $this->assertSame('/app/teste-contexto',parse_url($r->headers->get('Location'),PHP_URL_PATH));
        $this->getJson($this->url('/teste-contexto',$id))->assertOk();
        $this->get($this->url('/teste-pdf',$id))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertSee('%PDF-ficticio');
    }

    public function test_escolha_varios_unico_e_sem_vinculo_e_token_nunca_aparece_no_html(): void
    {
        $item = ['id' => 1, 'perfil' => 'professor', 'espaco' => ['nome' => 'Escola fictícia']];
        $this->fingir(['*' => Http::response(['data' => [$item, array_merge($item, ['id' => 2])]])]);
        $this->get('/app/vinculos')->assertOk()->assertSee('Escolha seu vínculo')->assertDontSee('id="vinculo-unico"', false);
        $this->fingir(['*' => Http::response(['data' => [$item]])]);
        $this->get('/app/vinculos')->assertOk()->assertDontSee('id="vinculo-unico"', false);
        $this->get('/app/vinculos?trocar=1')->assertOk()->assertDontSee('id="vinculo-unico"', false);
        $this->fingir(['*' => Http::response(['data' => []])]);
        $this->get('/app/vinculos')->assertOk()->assertSee('Nenhum vínculo disponível');
        $this->fingir(['*' => Http::response(['data' => $this->dados('segredo-nao-publicar')], 201)]);
        $r = $this->post('/app/vinculos', ['vinculo_id' => 1])->assertRedirect();
        $this->assertStringNotContainsString('segredo-nao-publicar', $r->headers->get('Location'));
        $this->assertStringNotContainsString('_ctx',$r->headers->get('Location'));
        $this->assertSame('/app/painel',parse_url($r->headers->get('Location'),PHP_URL_PATH));
        $this->assertSame('segredo-nao-publicar', ContextoWeb::resolverReferencia('vinculo-1')['dados']['token']);
        $this->assertSame('identidade', session('auth_token'));
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer identidade'));
    }

    public function test_menu_e_topo_usam_contexto_e_logout_usa_identidade(): void
    {
        $id = $this->ativar($this->dados('contextual'));
        $this->fingir(fn($r)=>Http::response(['data'=>str_ends_with($r->url(),'/auth/vinculos') ? [
            ['id'=>1,'perfil'=>'professor','espaco'=>['id'=>1,'tipo'=>'escola','nome'=>'Escola fictícia']],
            ['id'=>2,'perfil'=>'professor','espaco'=>['id'=>2,'tipo'=>'escola','nome'=>'Outra escola']],
        ] : $this->dados('omitido')]));
        $this->get($this->url('/referencia-visual',$id))->assertOk()->assertSee('Trocar vínculo')->assertSee('Escola fictícia')->assertDontSee('contextual');
        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('auth_token');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/auth/logout') && $r->hasHeader('Authorization', 'Bearer identidade'));
    }
}
