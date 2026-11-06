<?php
namespace Tests\Feature;

use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GestaoInternaWebTest extends TestCase
{
    use \Tests\Support\ContextoSelecionado;
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Desenvolvedor fictício']);
        $this->withCookie(config('session.cookie'),session()->getId()); $this->withCredentials();
    }
    private function dados(string $perfil='desenvolvedor'): array
    {
        return ['acesso_interno_id'=>1,'token'=>'token-interno','tipo_contexto'=>'interno','perfil'=>$perfil,
            'permissoes'=>$perfil==='desenvolvedor' ? ['acesso_interno.gerir'] : ['cliente.ver']];
    }
    public function test_menu_lista_e_envio_usam_contexto_e_nao_expoem_token(): void
    {
        $id = $this->selecionarContextoFixture($this->dados());
        Http::fake(function ($r) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            $this->assertTrue($r->hasHeader('Authorization','Bearer token-interno'));
            if (str_ends_with($r->url(),'/auth/me')) return Http::response(['data'=>$this->dados()]);
            if ($r->method()==='POST') return Http::response(['data'=>['id'=>2]],201);
            return Http::response(['data'=>['data'=>[['id'=>2,'nome'=>'Suporte fictício','perfil'=>'suporte','situacao'=>'ativo','fim_em'=>null,'versao_autorizacao'=>1]],'current_page'=>1,'last_page'=>2]]);
        });
        $this->get('/app/painel')->assertOk()->assertSee('Acessos internos');
        $this->get('/app/acessos-internos')->assertOk()->assertSee('Conceder acesso')->assertSee('Suporte fictício')
            ->assertSee('Próxima')->assertDontSee('token-interno')->assertDontSee('value="desenvolvedor"',false);
        $this->post('/app/acessos-internos',['email'=>'novo@example.test','perfil'=>'financeiro','fim_em'=>'2026-12-01','motivo'=>'Ensaio aprovado'])
            ->assertRedirect(route('internos.index',[]))->assertSessionHas('sucesso');
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['fim_em']==='2026-12-02T02:59:59+00:00' && !isset($r['_ctx']) && $r['perfil']==='financeiro');
    }
    public function test_conflito_de_versao_nao_reenvia_operacao(): void
    {
        $id = $this->selecionarContextoFixture($this->dados());
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me') ? Http::response(['data'=>$this->dados()]) : Http::response([],409));
        $this->put('/app/acessos-internos/2',['situacao'=>'suspenso','fim_em'=>null,'versao_autorizacao'=>2,'motivo'=>'Suspensão de teste'])
            ->assertRedirect()->assertSessionHas('erro','O acesso mudou. Confira a versão atual e tente novamente.');
        Http::assertSentCount(2);
    }
    public function test_financeiro_nao_ve_menu_nem_consegue_enviar_concessao(): void
    {
        $id = $this->selecionarContextoFixture($this->dados('financeiro'));
        Http::fake(['*'=>Http::response(['data'=>$this->dados('financeiro')])]);
        $this->get('/app/painel')->assertOk()->assertDontSee('Acessos internos');
        $this->get('/app/acessos-internos')->assertForbidden();
        $this->post('/app/acessos-internos',[])->assertForbidden();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/v1/acessos-internos'));
    }
}
