<?php
namespace Tests\Feature;

use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContextoInternoWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_internos'=>true,
            'auth_nome'=>'Operador fictício','auth_perfil'=>null,'auth_permissoes'=>[],'auth_titular'=>true]);
        $this->withCookie(config('session.cookie'),session()->getId());
        $this->withCredentials();
        $this->withHeader('X-G360-Revisao', ContextoWeb::garantirRevisao());
    }

    private function dados(string $perfil = 'financeiro'): array
    {
        return ['token'=>'token-interno-secreto','tipo_contexto'=>'interno','acesso_interno_id'=>1,'perfil'=>$perfil,
            'permissoes'=>['cliente.ver','cliente.gerir'],'nome'=>'Operador fictício','email'=>'a1b@example.test',
            'cpf'=>'00000000001','created_at'=>'2026-01-01','escola_nome'=>null];
    }

    public function test_escolha_explicita_inclui_internos_sem_autoentrar_ou_expor_token(): void
    {
        Http::fake(function ($r) {
            $this->assertTrue($r->hasHeader('Authorization','Bearer identidade'));
            if (str_ends_with($r->url(),'/selecionar')) return Http::response(['data'=>$this->dados()],201);
            return Http::response(['data'=>str_ends_with($r->url(),'/acessos-internos') ? [['id'=>1,'perfil'=>'financeiro']] :
                [['id'=>1,'perfil'=>'professor','espaco'=>['nome'=>'Escola fictícia']]]]);
        });
        $this->get('/app/vinculos')->assertOk()->assertSee('Escolha seu acesso')->assertSee('Financeiro')
            ->assertSee('Escola fictícia')->assertDontSee('id="vinculo-unico"',false);
        $r = $this->post('/app/vinculos/interno',['acesso_interno_id'=>1])->assertRedirect();
        $this->assertStringNotContainsString('token-interno-secreto',$r->headers->get('Location'));
        $this->assertSame(route('painel'),$r->headers->get('Location'));
        $this->assertSame('interno',ContextoWeb::resolverReferencia('interno-1')['dados']['tipo_contexto']);
        $this->assertNull(parse_url($r->headers->get('Location'),PHP_URL_QUERY));
        $this->assertSame('identidade',session('auth_token'));
        $this->assertNull(session('auth_perfil'));
    }

    public function test_painel_perfil_menu_e_rotas_nao_vazam_contexto_pedagogico(): void
    {
        $this->withHeader('X-G360-Revisao', ContextoWeb::ativar($this->dados()));
        Http::fake(function ($r) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            $this->assertTrue($r->hasHeader('Authorization','Bearer token-interno-secreto'));
            $this->assertStringEndsWith('/auth/me',$r->url());
            return Http::response(['data'=>$this->dados()]);
        });
        $this->get('/app/painel')->assertOk()->assertSee('Área interna do Gabarito360')->assertSee('Financeiro')
            ->assertDontSee('token-interno-secreto')->assertDontSee('href="'.route('provas.index').'"',false)->assertDontSee('Assinatura e cobrança');
        $this->get('/app/perfil')->assertOk()->assertSee('Financeiro')->assertDontSee('token-interno-secreto');
        foreach (['provas','alunos','turmas','configuracoes'] as $url) $this->get('/app/'.$url)->assertForbidden();
    }

    public function test_troca_interna_invalida_tela_anterior_e_revogacao_exige_nova_selecao(): void
    {
        $revision = ContextoWeb::ativar($this->dados());
        $a = session('auth_contexto_ativo');
        $dev = array_merge($this->dados('desenvolvedor'),['token'=>'token-dev','acesso_interno_id'=>2]);
        $nova = ContextoWeb::ativar($dev);
        $b = session('auth_contexto_ativo');
        $this->assertNull(ContextoWeb::obter($a));
        Http::fake(['*'=>Http::response([],403)]);
        $this->withHeader('X-G360-Revisao', $revision)->postJson('/app/perfil/email',[])->assertStatus(409);
        Http::assertNothingSent();
        $this->withHeader('X-G360-Revisao', $nova)->postJson('/app/perfil/email',[])->assertStatus(409)->assertJsonPath('selecionar_vinculo',true);
        $this->assertNull(ContextoWeb::obter($b));
        $this->assertSame('identidade',session('auth_token'));
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($r)=>str_ends_with($r->url(),'/auth/email'));
    }

    public function test_habilitacao_removida_preserva_selecao_escolar_e_falha_selecao_nao_cria_contexto(): void
    {
        Http::fake(fn ($r)=>Http::response(['data'=>[]],str_contains($r->url(),'acessos-internos') ? 404 : 200));
        $this->get('/app/vinculos')->assertOk()->assertSee('Nenhum vínculo disponível')->assertDontSee('Equipe interna');
        $this->post('/app/vinculos/interno',['acesso_interno_id'=>1])->assertRedirect(route('vinculos.index',['trocar'=>1]))->assertSessionHas('erro');
        $this->assertSame('identidade',session('auth_token'));
    }
}
