<?php
namespace Tests\Feature;

use App\Services\ApiClient;
use App\Support\ContextoWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Http,Route};
use Tests\TestCase;

/** Testes adversariais UR3: contrato público, sem overrides do cliente HTTP de testes. */
class ContextoSessaoAdversarialTest extends TestCase
{
    private const PROBE='/app/_teste-contexto-sessao';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade-ficticia','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        Route::middleware(['web','autenticado'])->get(self::PROBE,function(){
            return response()->json(['perfil'=>ContextoWeb::atual()['perfil']??null,
                'espaco'=>ContextoWeb::atual()['espaco']['id']??null,'revisao'=>ContextoWeb::revisao()]);
        });
        Route::middleware(['web','autenticado'])->match(['POST','PUT','PATCH','DELETE'],self::PROBE,function(Request $r){
            app(ApiClient::class)->post('/v1/operacao-adversarial',['entrada'=>$r->input('entrada')]);
            return response()->json(['feito'=>true]);
        });
    }

    private function contexto(int $id,string $perfil='professor',int $espaco=13): array
    {
        return ['id'=>4,'vinculo_id'=>$id,'tipo_contexto'=>'vinculo','token'=>'contexto-ficticio-'.$id,
            'perfil'=>$perfil,'permissoes'=>['turma.ver','aluno.ver','prova.ver','resultado.ver','correcao.acompanhar'],
            'espaco'=>['id'=>$espaco,'tipo'=>'escola','nome'=>'Escola fictícia '.$espaco]];
    }

    private function ativar(int $id=7,string $perfil='professor',int $espaco=13): string
    {
        return ContextoWeb::ativar($this->contexto($id,$perfil,$espaco));
    }

    private function api(array $contextos=[],int $statusMe=200): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        $contextos += [7=>$this->contexto(7),8=>$this->contexto(8,'coordenador',14)];
        Http::fake(function($r)use($contextos,$statusMe){
            $path=parse_url($r->url(),PHP_URL_PATH);
            if($path==='/api/v1/auth/logout') {
                $this->assertTrue($r->hasHeader('Authorization','Bearer identidade-ficticia'));
                return Http::response(['data'=>[]]);
            }
            if($path==='/api/v1/auth/vinculos/selecionar') {
                $this->assertTrue($r->hasHeader('Authorization','Bearer identidade-ficticia'));
                return Http::response(['data'=>$contextos[(int)$r['vinculo_id']]],201);
            }
            foreach($contextos as $id=>$dados) {
                if(!$r->hasHeader('Authorization','Bearer contexto-ficticio-'.$id))continue;
                if($path==='/api/v1/auth/me')return Http::response(['data'=>$dados],$statusMe);
                if($path==='/api/v1/operacao-adversarial')return Http::response(['data'=>[]]);
            }
            $this->fail('Request inesperado: '.$r->method().' '.$path);
        });
    }

    public function test_mudanca_de_escola_na_outra_aba_recusa_todos_os_metodos_antigos_antes_da_api(): void
    {
        $antiga=$this->ativar(7);$nova=$this->ativar(8,'coordenador',14);$this->api();
        $this->assertNotSame($antiga,$nova);
        foreach(['POST','PUT','PATCH','DELETE'] as $metodo)
            $this->json($metodo,self::PROBE,['_revisao'=>$antiga,'entrada'=>'não executar'])->assertStatus(409);
        Http::assertNothingSent();
        $this->postJson(self::PROBE,['_revisao'=>$nova,'entrada'=>'válida'])->assertOk();
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/operacao-adversarial')
            && $r->hasHeader('Authorization','Bearer contexto-ficticio-8') && $r['entrada']==='válida');
    }

    public function test_mesma_escola_outro_perfil_nao_concede_projecao_nova_a_get_da_aba_antiga(): void
    {
        $antiga=$this->ativar(7);$nova=$this->ativar(8,'coordenador',13);
        $this->api([8=>$this->contexto(8,'coordenador',13)]);
        $this->getJson(self::PROBE,['X-G360-Revisao'=>$antiga])->assertStatus(409);
        $this->head(self::PROBE,['Accept'=>'application/json','X-G360-Revisao'=>$antiga])->assertStatus(409);
        Http::assertNothingSent();
        $this->getJson(self::PROBE,['X-G360-Revisao'=>$nova])->assertOk()->assertJsonPath('perfil','coordenador')->assertJsonPath('espaco',13);
    }

    public function test_revisao_ausente_array_ou_fontes_conflitantes_nao_executam_mutacao(): void
    {
        $rev=$this->ativar();$this->api();
        $this->postJson(self::PROBE,['entrada'=>'faltante'])->assertStatus(409);
        $this->postJson(self::PROBE,['_revisao'=>[$rev]])->assertStatus(400);
        $this->postJson(self::PROBE,['_revisao'=>$rev],['X-G360-Revisao'=>'revisao-antiga'])->assertStatus(409);
        $this->postJson(self::PROBE,['_revisao'=>'antiga'],['X-G360-Revisao'=>$rev])->assertStatus(409);
        $this->postJson(self::PROBE.'?_revisao='.$rev,['_revisao'=>$rev])->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_ajax_sem_revisao_nao_busca_dados_e_navegacao_html_carrega_contexto_atual(): void
    {
        $this->ativar();$nova=$this->ativar(8,'coordenador',14);$this->api();
        $this->get(self::PROBE,['X-Requested-With'=>'XMLHttpRequest'])->assertStatus(409);
        $this->getJson(self::PROBE)->assertStatus(409);
        Http::assertNothingSent();
        $this->get(self::PROBE)->assertOk()->assertJsonPath('revisao',$nova)->assertJsonPath('espaco',14);
    }

    public function test_fotos_e_pdfs_nativos_sem_revisao_ou_com_revisao_antiga_falham_antes_de_bytes(): void
    {
        $antiga=$this->ativar();$this->ativar(8,'coordenador',13);$this->api();
        $urls=[route('perfil.foto'),route('alunos.foto',['id'=>3]),
            route('cartoes.imagem-segura',['id'=>65,'tipo'=>'original']),
            route('resultados.pdf',['alunoId'=>3,'provaId'=>4]),route('correcao.pdf',['id'=>4])];
        foreach($urls as $url) {
            $this->get($url)->assertStatus(409)->assertDontSee('bytes-ficticios');
            $this->get($url,['X-G360-Revisao'=>$antiga])->assertStatus(409);
        }
        Http::assertNothingSent();
    }

    public function test_duas_selecoes_da_mesma_revisao_so_confirmam_a_primeira_sem_codigo_na_url(): void
    {
        $rev=$this->ativar();$this->api();
        $this->postJson('/app/vinculos',['_revisao'=>$rev,'vinculo_id'=>8])->assertOk()
            ->assertJsonPath('url',route('painel'))->assertDontSee('contexto-ficticio')->assertDontSee('/acessos/');
        $this->postJson('/app/vinculos',['_revisao'=>$rev,'vinculo_id'=>7])->assertStatus(409);
        $selecoes=Http::recorded(fn($r)=>str_ends_with($r->url(),'/vinculos/selecionar'));
        $this->assertCount(1,$selecoes);
        $this->assertSame('/app/painel',parse_url(route('painel'),PHP_URL_PATH));
        $this->assertNull(parse_url(route('painel'),PHP_URL_QUERY));
    }

    public function test_selecionar_a_mesma_opcao_rotaciona_revisao_e_nao_ressuscita_formulario(): void
    {
        $antes=$this->ativar();$depois=$this->ativar();$this->api();
        $this->assertNotSame($antes,$depois);
        $this->postJson(self::PROBE,['_revisao'=>$antes])->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_contexto_cacheado_de_outra_identidade_nao_e_adotado_mesmo_com_revisao_igual(): void
    {
        $rev=$this->ativar();$this->withSession(['auth_token'=>'identidade-de-outra-conta']);$this->api();
        $this->postJson(self::PROBE,['_revisao'=>$rev])->assertStatus(409);
        Http::assertNothingSent();
    }

    public function test_revogacao_api_impede_efeito_mesmo_com_revisao_atual(): void
    {
        $rev=$this->ativar();$this->api(statusMe:403);
        $this->postJson(self::PROBE,['_revisao'=>$rev])->assertStatus(409);
        Http::assertSentCount(1);
        Http::assertNotSent(fn($r)=>str_ends_with($r->url(),'/operacao-adversarial'));
    }

    public function test_falha_api_nao_troca_para_token_de_identidade_nem_executa(): void
    {
        $rev=$this->ativar();$this->api(statusMe:503);
        $this->postJson(self::PROBE,['_revisao'=>$rev])->assertStatus(503);
        Http::assertSentCount(1);
        Http::assertSent(fn($r)=>$r->hasHeader('Authorization','Bearer contexto-ficticio-7'));
        Http::assertNotSent(fn($r)=>str_ends_with($r->url(),'/operacao-adversarial'));
    }

    public function test_resposta_me_de_outro_vinculo_nao_autoriza_operacao(): void
    {
        $rev=$this->ativar();$this->api([7=>$this->contexto(999)]);
        $this->postJson(self::PROBE,['_revisao'=>$rev])->assertStatus(503);
        Http::assertNotSent(fn($r)=>str_ends_with($r->url(),'/operacao-adversarial'));
    }

    public function test_get_antigo_nao_seleciona_vinculo_e_post_antigo_nao_e_reexecutado(): void
    {
        $rev=$this->ativar();$this->api();
        $r=$this->get('/acessos/vinculo-8/turmas')->assertRedirect();
        $this->assertStringNotContainsString('/acessos/',$r->headers->get('Location'));
        $this->assertStringNotContainsString('vinculo-8',$r->headers->get('Location'));
        $this->postJson('/acessos/vinculo-8/turmas',['_revisao'=>$rev])->assertStatus(409);
        Http::assertNothingSent();
        $this->getJson(self::PROBE,['X-G360-Revisao'=>$rev])->assertOk()->assertJsonPath('espaco',13);
        Http::assertSent(fn($r)=>$r->hasHeader('Authorization','Bearer contexto-ficticio-7'));
    }

    public function test_logout_antigo_nao_encerra_novo_contexto_e_logout_atual_usa_identidade(): void
    {
        $antiga=$this->ativar();$nova=$this->ativar(8);$this->api();
        $this->postJson('/logout',['_revisao'=>$antiga])->assertStatus(409);
        Http::assertNothingSent();
        $this->assertSame('identidade-ficticia',session('auth_token'));
        $this->post('/logout',['_revisao'=>$nova])->assertRedirect(route('login'));
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/auth/logout') && $r->hasHeader('Authorization','Bearer identidade-ficticia'));
        $this->assertNull(session('auth_token'));
        Http::swap(new \Illuminate\Http\Client\Factory());Http::preventStrayRequests();
        $this->postJson(self::PROBE,['_revisao'=>$nova])->assertStatus(401);
        Http::assertNothingSent();
    }

    public function test_revisao_atual_nao_substitui_csrf_real(): void
    {
        $rev=$this->ativar();$this->api();
        $this->withSession(['_token'=>'csrf-ficticio-conhecido']);$this->app->instance('env','local');
        try {
            $this->post(self::PROBE,['_revisao'=>$rev,'_token'=>'invalido'])->assertStatus(419);
            Http::assertNothingSent();
            $this->post(self::PROBE,['_revisao'=>$rev,'_token'=>'csrf-ficticio-conhecido'])->assertOk();
            Http::assertSentCount(2);
        } finally {$this->app->instance('env','testing');}
    }
}
