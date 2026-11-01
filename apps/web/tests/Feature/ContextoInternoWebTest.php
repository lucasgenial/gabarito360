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
        $this->get('/vinculos')->assertOk()->assertSee('Escolha seu acesso')->assertSee('Financeiro')
            ->assertSee('Escola fictícia')->assertDontSee('id="vinculo-unico"',false);
        $r = $this->post('/vinculos/interno',['acesso_interno_id'=>1])->assertRedirect();
        $this->assertStringNotContainsString('token-interno-secreto',$r->headers->get('Location'));
        parse_str(parse_url($r->headers->get('Location'),PHP_URL_QUERY),$q);
        $this->assertSame('interno',ContextoWeb::obter($q['_ctx'])['tipo_contexto']);
        $this->assertSame('identidade',session('auth_token'));
        $this->assertNull(session('auth_perfil'));
    }

    public function test_painel_perfil_menu_e_rotas_nao_vazam_contexto_pedagogico(): void
    {
        $id = ContextoWeb::criar($this->dados());
        Http::fake(function ($r) {
            $this->assertTrue($r->hasHeader('Authorization','Bearer token-interno-secreto'));
            $this->assertStringEndsWith('/auth/me',$r->url());
            return Http::response(['data'=>$this->dados()]);
        });
        $this->get('/painel?_ctx='.$id)->assertOk()->assertSee('Área interna do Gabarito360')->assertSee('Financeiro')
            ->assertDontSee('token-interno-secreto')->assertDontSee('href="http://localhost/provas"',false)->assertDontSee('Assinatura e cobrança');
        $this->get('/perfil?_ctx='.$id)->assertOk()->assertSee('Financeiro')->assertDontSee('token-interno-secreto');
        foreach (['provas','alunos','turmas','configuracoes'] as $url) $this->get('/'.$url.'?_ctx='.$id)->assertForbidden();
    }

    public function test_duas_abas_preservam_perfil_e_revogacao_nao_afeta_irma(): void
    {
        $a = ContextoWeb::criar($this->dados());
        $dev = array_merge($this->dados('desenvolvedor'),['token'=>'token-dev']);
        $b = ContextoWeb::criar($dev);
        $revogado = false;
        Http::fake(function ($r) use (&$revogado,$dev) {
            if ($r->hasHeader('Authorization','Bearer token-dev')) return Http::response(['data'=>$dev]);
            return $revogado ? Http::response([],403) : Http::response(['data'=>$this->dados()]);
        });
        $this->get('/painel?_ctx='.$a)->assertOk()->assertSee('Financeiro');
        $this->get('/painel?_ctx='.$b)->assertOk()->assertSee('Desenvolvedor');
        $revogado = true;
        $this->postJson('/perfil/email',['_ctx'=>$a])->assertStatus(409);
        $this->assertNull(ContextoWeb::obter($a));
        $this->get('/painel?_ctx='.$b)->assertOk()->assertSee('Desenvolvedor');
        $this->assertSame('identidade',session('auth_token'));
        Http::assertNotSent(fn ($r)=>str_ends_with($r->url(),'/auth/email'));
    }

    public function test_habilitacao_removida_preserva_selecao_escolar_e_falha_selecao_nao_cria_contexto(): void
    {
        Http::fake(fn ($r)=>Http::response(['data'=>[]],str_contains($r->url(),'acessos-internos') ? 404 : 200));
        $this->get('/vinculos')->assertOk()->assertSee('Nenhum vínculo disponível')->assertDontSee('Equipe interna');
        $this->post('/vinculos/interno',['acesso_interno_id'=>1])->assertRedirect(route('vinculos.index',['trocar'=>1]))->assertSessionHas('erro');
        $this->assertSame('identidade',session('auth_token'));
    }
}
