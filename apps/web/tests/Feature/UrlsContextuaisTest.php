<?php
namespace Tests\Feature;

use App\Services\ApiClient;
use App\Support\{Acesso,ContextoWeb};
use Illuminate\Support\Facades\{Http,Route};
use Tests\TestCase;

class UrlsContextuaisTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade-ficticia','auth_vinculos'=>true,'auth_nome'=>'Teste']);
        $this->withCookie(config('session.cookie'),session()->getId()); $this->withCredentials();
        Route::middleware(['web','autenticado'])->get('/acessos/{acesso}/teste-ur1/{id}',function(int $id){
            return response()->json(['id'=>$id,'perfil'=>Acesso::perfil(),'turma'=>route('turmas.show',$id),
                'resultado'=>route('resultados.show',[$id,4])]);
        })->name('teste-ur1');
        Route::middleware(['web','autenticado'])->post('/acessos/{acesso}/teste-ur1/{id}',function(int $id){
            app(ApiClient::class)->post('/v1/operacao',['id'=>$id]);
            return redirect('/turmas/'.$id.'#disciplinas');
        });
    }

    private function dados(int $vinculo,string $perfil='professor'): array
    {
        return ['vinculo_id'=>$vinculo,'token'=>'segredo-'.$vinculo,'perfil'=>$perfil,'permissoes'=>['turma.ver'],
            'espaco'=>['id'=>1,'tipo'=>'escola','nome'=>'Escola fictícia']];
    }

    private function api(): void
    {
        Http::fake(function($r){
            if(str_ends_with($r->url(),'/operacao'))return Http::response(['data'=>[]]);
            $v=$r->hasHeader('Authorization','Bearer segredo-7')?7:8;
            return Http::response(['data'=>$this->dados($v,$v===7?'coordenador':'professor')]);
        });
    }

    public function test_duas_abas_conservam_vinculos_e_geram_rotas_sem_handle_ou_token(): void
    {
        $a=ContextoWeb::criar($this->dados(7,'coordenador')); $b=ContextoWeb::criar($this->dados(8));$this->api();
        foreach([7,8,7] as $v){
            $r=$this->get('/acessos/vinculo-'.$v.'/teste-ur1/14')->assertOk()->assertJsonPath('id',14)
                ->assertJsonPath('perfil',$v===7?'coordenador':'professor')->assertDontSee($a)->assertDontSee($b)->assertDontSee('segredo-')->assertDontSee('_ctx');
            $this->assertSame('/acessos/vinculo-'.$v.'/turmas/14',parse_url($r->json('turma'),PHP_URL_PATH));
            $this->assertSame('/acessos/vinculo-'.$v.'/resultados/14/4',parse_url($r->json('resultado'),PHP_URL_PATH));
        }
        $this->assertSame('identidade-ficticia',session('auth_token'));
        $this->assertSame('sessao',app('url')->getDefaultParameters()['acesso']);
    }

    public function test_handle_legado_so_migra_get_e_nao_reexecuta_formulario_antigo(): void
    {
        $id=ContextoWeb::criar($this->dados(7));
        $r=$this->get('/turmas/14?_ctx='.$id.'&busca=teste')->assertRedirect();
        $this->assertSame('/acessos/vinculo-7/turmas/14',parse_url($r->headers->get('Location'),PHP_URL_PATH));
        $this->assertStringContainsString('busca=teste',$r->headers->get('Location'));
        $this->assertStringNotContainsString($id,$r->headers->get('Location'));
        $this->assertStringNotContainsString('_ctx',$r->headers->get('Location'));
        $this->put('/turmas/14',['_ctx'=>$id])->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_nao_admite_outro_contexto_tipo_sessao_ou_fontes_conflitantes(): void
    {
        $id=ContextoWeb::criar($this->dados(7));
        $this->getJson('/acessos/vinculo-8/teste-ur1/14')->assertStatus(409);
        $this->getJson('/acessos/pessoal-7/teste-ur1/14')->assertStatus(409);
        $this->getJson('/acessos/sessao/teste-ur1/14')->assertStatus(409);
        $this->getJson('/acessos/vinculo-7/teste-ur1/14',['X-G360-Acesso'=>'vinculo-8'])->assertStatus(409);
        $this->getJson('/acessos/vinculo-7/teste-ur1/14?_ctx='.$id)->assertStatus(409);
        $this->withSession(['auth_token'=>'outra-identidade']);session()->save();
        $this->getJson('/acessos/vinculo-7/teste-ur1/14')->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_revalida_revogacao_e_indisponibilidade_sem_fallback(): void
    {
        ContextoWeb::criar($this->dados(7));
        Http::fake(['*'=>Http::response([],403)]);
        $this->getJson('/acessos/vinculo-7/teste-ur1/14')->assertStatus(409);
        $this->assertNull(ContextoWeb::resolverReferencia('vinculo-7'));
        ContextoWeb::criar($this->dados(8));
        Http::swap(new \Illuminate\Http\Client\Factory());Http::preventStrayRequests();Http::fake(['*'=>Http::response([],503)]);
        $this->getJson('/acessos/vinculo-8/teste-ur1/14')->assertStatus(503);
        $this->assertNotNull(ContextoWeb::resolverReferencia('vinculo-8'));
    }

    public function test_expiracao_nao_envia_operacao_e_retorno_de_post_e_limpo(): void
    {
        ContextoWeb::criar($this->dados(7));$this->api();
        $r=$this->post('/acessos/vinculo-7/teste-ur1/14')->assertRedirect();
        $this->assertSame('/acessos/vinculo-7/turmas/14',parse_url($r->headers->get('Location'),PHP_URL_PATH));
        $this->assertSame('disciplinas',parse_url($r->headers->get('Location'),PHP_URL_FRAGMENT));
        $this->assertStringNotContainsString('_ctx',$r->headers->get('Location'));
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/operacao')&&$r->hasHeader('Authorization','Bearer segredo-7'));
        Http::swap(new \Illuminate\Http\Client\Factory());Http::preventStrayRequests();
        $this->travel(121)->minutes();
        $this->postJson('/acessos/vinculo-7/teste-ur1/14')->assertStatus(409);Http::assertNothingSent();
    }

    public function test_selecao_por_post_devolve_somente_referencia_de_negocio(): void
    {
        Http::fake(['*'=>Http::response(['data'=>$this->dados(7)],201)]);
        $r=$this->postJson('/vinculos',['vinculo_id'=>7])->assertOk();
        $this->assertSame('/acessos/vinculo-7/painel',parse_url($r->json('url'),PHP_URL_PATH));
        $r->assertDontSee('segredo-7')->assertDontSee('_ctx');
    }

    public function test_regime_legado_conserva_identidade_sem_adotar_referencia_de_vinculo(): void
    {
        $this->withSession(['auth_vinculos'=>false]);
        Route::middleware(['web','autenticado'])->post('/teste-ur1-legado',function(){
            $r=app(ApiClient::class)->post('/v1/operacao',['valor'=>1]);
            return response()->json(['status'=>$r->status(),'contexto'=>ContextoWeb::referencia()]);
        })->name('legado.teste-ur1-legado');
        Http::fake(['*'=>Http::response([],403)]);
        $this->post('/teste-ur1-legado')->assertOk()->assertJsonPath('status',403)->assertJsonPath('contexto',null);
        Http::assertSent(fn($r)=>$r->hasHeader('Authorization','Bearer identidade-ficticia'));
        Http::swap(new \Illuminate\Http\Client\Factory());Http::preventStrayRequests();
        ContextoWeb::criar($this->dados(7));
        $this->postJson('/acessos/vinculo-7/teste-ur1/14')->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_csrf_real_rejeita_formulario_invalido_e_admite_token_da_sessao(): void
    {
        ContextoWeb::criar($this->dados(7));$this->api();
        // O framework pula CSRF no ambiente testing; esta verificação exercita o middleware real.
        $this->app->instance('env','local');
        $this->withSession(['_token'=>'csrf-ficticio-conhecido']);
        $this->post('/acessos/vinculo-7/teste-ur1/14',['_token'=>'incorreto'])->assertStatus(419);
        Http::assertNothingSent();
        $this->post('/acessos/vinculo-7/teste-ur1/14',['_token'=>'csrf-ficticio-conhecido'])->assertRedirect();
        Http::assertSentCount(2);
    }
}
