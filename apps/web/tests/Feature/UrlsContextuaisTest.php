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
        $this->withHeader('X-G360-Revisao', ContextoWeb::garantirRevisao());
        Route::middleware(['web','autenticado'])->get('/app/teste-ur3/{id}',function(int $id){
            return response()->json(['id'=>$id,'perfil'=>Acesso::perfil(),'turma'=>route('turmas.show',$id),
                'resultado'=>route('resultados.show',[$id,4])]);
        })->name('teste-ur3');
        Route::middleware(['web','autenticado'])->post('/app/teste-ur3/{id}',function(int $id){
            app(ApiClient::class)->post('/v1/operacao',['id'=>$id]);
            return redirect('/turmas/'.$id.'#disciplinas');
        });
    }

    private function dados(int $vinculo,string $perfil='professor'): array
    {
        return ['vinculo_id'=>$vinculo,'token'=>'segredo-'.$vinculo,'perfil'=>$perfil,'permissoes'=>['turma.ver'],
            'espaco'=>['id'=>1,'tipo'=>'escola','nome'=>'Escola fictícia']];
    }

    private function ativar(array $dados): string
    {
        $this->withHeader('X-G360-Revisao', ContextoWeb::ativar($dados));
        session()->save();
        return session('auth_contexto_ativo');
    }

    private function api(): void
    {
        Http::fake(function($r){
            if(str_ends_with($r->url(),'/operacao'))return Http::response(['data'=>[]]);
            $v=$r->hasHeader('Authorization','Bearer segredo-7')?7:8;
            return Http::response(['data'=>$this->dados($v,$v===7?'coordenador':'professor')]);
        });
    }

    public function test_rotas_app_usam_snapshot_ativo_sem_ids_handle_ou_token(): void
    {
        $this->api();
        foreach([7,8,7] as $v){
            $id=$this->ativar($this->dados($v,$v===7?'coordenador':'professor'));
            $r=$this->getJson('/app/teste-ur3/14')->assertOk()->assertJsonPath('id',14)
                ->assertJsonPath('perfil',$v===7?'coordenador':'professor')->assertDontSee($id)->assertDontSee('segredo-')->assertDontSee('_ctx');
            $this->assertSame('/app/turmas/14',parse_url($r->json('turma'),PHP_URL_PATH));
            $this->assertSame('/app/resultados/14/4',parse_url($r->json('resultado'),PHP_URL_PATH));
            $this->assertNull(parse_url($r->json('turma'),PHP_URL_QUERY));
        }
        $this->assertSame('identidade-ficticia',session('auth_token'));
        $this->assertArrayNotHasKey('acesso',app('url')->getDefaultParameters());
    }

    public function test_get_legado_nunca_seleciona_contexto_e_nao_reexecuta_formulario(): void
    {
        $id=ContextoWeb::criar($this->dados(7));
        $this->get('/turmas/14?_ctx='.$id.'&busca=teste')->assertRedirect('/app/vinculos');
        $this->assertNull(session('auth_contexto_ativo'));
        $this->ativar($this->dados(8));
        $r=$this->get('/acessos/vinculo-7/turmas/14?_ctx='.$id.'&busca=teste')->assertRedirect();
        $this->assertSame('/app/turmas/14',parse_url($r->headers->get('Location'),PHP_URL_PATH));
        $this->assertStringContainsString('busca=teste',$r->headers->get('Location'));
        $this->assertStringNotContainsString($id,$r->headers->get('Location'));
        $this->assertStringNotContainsString('_ctx',$r->headers->get('Location'));
        $this->assertSame(8,ContextoWeb::obter(session('auth_contexto_ativo'))['vinculo_id']);
        $this->put('/turmas/14',['_ctx'=>$id])->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_fontes_contextuais_antigas_e_revisoes_conflitantes_sao_recusadas(): void
    {
        $id=$this->ativar($this->dados(7));
        foreach(['/acessos/vinculo-8/turmas/14','/acessos/pessoal-7/turmas/14','/acessos/sessao/turmas/14'] as $url) $this->getJson($url)->assertStatus(409);
        $this->getJson('/app/teste-ur3/14',['X-G360-Acesso'=>'vinculo-8'])->assertStatus(409);
        $this->getJson('/app/teste-ur3/14?_ctx='.$id)->assertStatus(409);
        $this->postJson('/app/teste-ur3/14',['_revisao'=>'revisao-divergente'])->assertStatus(409);
        $this->withSession(['auth_token'=>'outra-identidade']);session()->save();
        $this->getJson('/app/teste-ur3/14')->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_revalida_revogacao_e_indisponibilidade_sem_fallback(): void
    {
        $id=$this->ativar($this->dados(7));
        Http::fake(['*'=>Http::response([],403)]);
        $this->getJson('/app/teste-ur3/14')->assertStatus(409);
        $this->assertNull(ContextoWeb::obter($id));
        $id=$this->ativar($this->dados(8));
        Http::swap(new \Illuminate\Http\Client\Factory());Http::preventStrayRequests();Http::fake(['*'=>Http::response([],503)]);
        $this->getJson('/app/teste-ur3/14')->assertStatus(503);
        $this->assertNotNull(ContextoWeb::obter($id));
    }

    public function test_expiracao_nao_envia_operacao_e_retorno_de_post_e_limpo(): void
    {
        $this->ativar($this->dados(7));$this->api();
        $r=$this->post('/app/teste-ur3/14')->assertRedirect();
        $this->assertSame('/app/turmas/14',parse_url($r->headers->get('Location'),PHP_URL_PATH));
        $this->assertSame('disciplinas',parse_url($r->headers->get('Location'),PHP_URL_FRAGMENT));
        $this->assertStringNotContainsString('_ctx',$r->headers->get('Location'));
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/operacao')&&$r->hasHeader('Authorization','Bearer segredo-7'));
        Http::swap(new \Illuminate\Http\Client\Factory());Http::preventStrayRequests();
        $this->travel(121)->minutes();
        $this->postJson('/app/teste-ur3/14')->assertStatus(409);Http::assertNothingSent();
    }

    public function test_selecao_por_post_devolve_rota_fixa_e_nova_revisao_sem_credenciais(): void
    {
        $velha=session('auth_contexto_revisao');
        Http::fake(['*'=>Http::response(['data'=>$this->dados(7)],201)]);
        $r=$this->postJson('/app/vinculos',['vinculo_id'=>7])->assertOk();
        $this->assertSame('/app/painel',parse_url($r->json('url'),PHP_URL_PATH));
        $this->assertNotSame($velha,$r->json('revisao'));
        $r->assertDontSee('segredo-7')->assertDontSee('_ctx')->assertDontSee(session('auth_contexto_ativo'));
    }

    public function test_regime_legado_exige_selecao_explicita_e_conserva_identidade(): void
    {
        $this->withSession(['auth_vinculos'=>false]);
        Route::middleware(['web','autenticado'])->post('/app/teste-ur3-legado',function(){
            $r=app(ApiClient::class)->post('/v1/operacao',['valor'=>1]);
            return response()->json(['status'=>$r->status(),'contexto'=>ContextoWeb::atual()['tipo_contexto']]);
        });
        $this->postJson('/app/teste-ur3-legado')->assertStatus(409); Http::assertNothingSent();
        Http::fake(fn($r)=>Http::response(['data'=>['perfil'=>'professor','permissoes'=>['turma.ver']]],str_ends_with($r->url(),'/operacao')?403:200));
        $r=$this->postJson('/app/vinculos/legado')->assertOk();
        $this->withHeader('X-G360-Revisao',$r->json('revisao'))->postJson('/app/teste-ur3-legado')->assertOk()->assertJsonPath('status',403)->assertJsonPath('contexto','legado');
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/operacao')&&$r->hasHeader('Authorization','Bearer identidade-ficticia'));
        Http::swap(new \Illuminate\Http\Client\Factory());Http::preventStrayRequests();
        $this->getJson('/acessos/vinculo-7/turmas/14')->assertStatus(409); Http::assertNothingSent();
    }

    public function test_metadados_me_aceitam_nulo_inativo_e_recusam_identificador_malformado(): void
    {
        $this->ativar($this->dados(7));
        Http::fake(['*'=>Http::response(['data'=>array_merge($this->dados(7),['acesso_interno_id'=>null])])]);
        $this->getJson('/app/teste-ur3/14')->assertOk();
        foreach ([null, -1, ['id'=>7], '07', 999] as $valor) {
            Http::swap(new \Illuminate\Http\Client\Factory()); Http::preventStrayRequests();
            Http::fake(['*'=>Http::response(['data'=>array_merge($this->dados(7),['vinculo_id'=>$valor])])]);
            $this->postJson('/app/teste-ur3/14')->assertStatus(503);
            Http::assertSentCount(1);
            Http::assertNotSent(fn($r)=>str_ends_with($r->url(),'/operacao'));
        }
    }

    public function test_csrf_real_rejeita_formulario_invalido_e_admite_token_da_sessao(): void
    {
        $this->ativar($this->dados(7));$this->api();
        // Exerce o middleware real; o framework ignora CSRF por padrão no ambiente testing.
        $this->app->instance('env','local');
        $this->withSession(['_token'=>'csrf-ficticio-conhecido']); session()->save();
        $this->post('/app/teste-ur3/14',['_token'=>'incorreto'])->assertStatus(419);
        Http::assertNothingSent();
        $this->post('/app/teste-ur3/14',['_token'=>'csrf-ficticio-conhecido'])->assertRedirect();
        Http::assertSentCount(2);
    }
}
