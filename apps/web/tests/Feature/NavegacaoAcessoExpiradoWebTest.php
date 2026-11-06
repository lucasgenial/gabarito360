<?php
namespace Tests\Feature;

use App\Support\{ContextoWeb,NavegacaoWeb};
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Sessão de identidade viva não equivale a contexto de negócio válido. */
final class NavegacaoAcessoExpiradoWebTest extends TestCase
{
    private string $revisao;
    private int $executadas=0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withSession(['auth_token'=>'identidade-expiracao-ficticia','auth_vinculos'=>true]);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $this->revisao=ContextoWeb::ativar($this->contexto());
        Http::preventStrayRequests();
        foreach(app('router')->getRoutes() as $rota){
            if(!in_array($rota->getAction('ur4')['nome']??null,['painel','turmas.index','turmas.show','turmas.update'],true))continue;
            $acao=$rota->getAction();unset($acao['controller']);
            $acao['uses']=function(){ $this->executadas++; return response()->json(['controller_executado'=>true]); };
            $rota->setAction($acao);
        }
    }

    private function contexto(int $vinculo=7): array
    {
        return ['tipo_contexto'=>'vinculo','vinculo_id'=>$vinculo,'token'=>'contexto-expiracao-ficticio',
            'perfil'=>'coordenador','permissoes'=>['turma.ver','turma.gerir'],
            'espaco'=>['id'=>13,'tipo'=>'escola','nome'=>'Escola fictícia']];
    }

    private function expirarContexto(): void
    {
        $handle=session('auth_contexto_ativo');
        $this->assertNotNull(ContextoWeb::obter($handle));
        ContextoWeb::esquecer($handle);
        $this->assertNull(ContextoWeb::obter($handle));
        $this->assertSame($handle,session('auth_contexto_ativo'));
        $this->assertSame('identidade-expiracao-ficticia',session('auth_token'));
    }

    private function semEfeitos(): void
    {
        $this->assertSame(0,$this->executadas);Http::assertNothingSent();
    }

    public function test_get_painel_e_listas_com_identidade_viva_e_cache_expirado_encaminham_seletor(): void
    {
        $this->expirarContexto();
        foreach(['/app/painel','/app/turmas','/app/provas','/app/alunos'] as $path)
            $this->get($path)->assertRedirect('/app/vinculos')->assertDontSee('controller_executado');
        $this->assertSame($this->revisao,session('auth_contexto_revisao'));
        $this->assertSame('identidade-expiracao-ficticia',session('auth_token'));
        $this->semEfeitos();
    }

    public function test_hidratacao_com_ref_ainda_viva_e_contexto_expirado_devolve_selecao_sem_executar(): void
    {
        $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);$this->expirarContexto();
        $this->assertNotNull(NavegacaoWeb::obter($d['referencia']));
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao],['X-G360-Hidratar'=>'1'])
            ->assertStatus(409)->assertJsonPath('selecionar_vinculo',true)
            ->assertJsonPath('url',fn($url)=>is_string($url)&&parse_url($url,PHP_URL_PATH)==='/app/vinculos'
                &&parse_url($url,PHP_URL_QUERY)===null&&parse_url($url,PHP_URL_FRAGMENT)===null)
            ->assertDontSee('controller_executado');
        $this->assertSame($this->revisao,session('auth_contexto_revisao'));
        $this->semEfeitos();
    }

    public function test_mutacao_nativa_com_contexto_expirado_nao_reaplica_nem_seleciona_automaticamente(): void
    {
        $d=NavegacaoWeb::destino('turmas.update',['id'=>14]);$this->expirarContexto();
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao,'nome'=>'Não gravar'])
            ->assertStatus(409)->assertJsonPath('selecionar_vinculo',true);
        $this->assertSame($this->revisao,session('auth_contexto_revisao'));
        $this->semEfeitos();
    }

    public function test_contexto_e_referencia_expirados_juntos_priorizam_recuperar_vinculo(): void
    {
        $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);$this->expirarContexto();
        $this->travel((int)config('session.lifetime')+1)->minutes();
        try{
            $this->assertNull(NavegacaoWeb::obter($d['referencia']));
            $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao],['X-G360-Hidratar'=>'1'])
                ->assertStatus(409)->assertJsonPath('selecionar_vinculo',true);
        }finally{$this->travelBack();}
        $this->assertSame($this->revisao,session('auth_contexto_revisao'));
        $this->semEfeitos();
    }

    public function test_formulario_nativo_sem_contexto_vai_ao_seletor_sem_consumir_alvo_ou_executar(): void
    {
        $d=NavegacaoWeb::destino('turmas.update',['id'=>14]);$this->expirarContexto();
        $this->post($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao,
            '_token'=>csrf_token(),'_method'=>'PUT','nome'=>'Não gravar'],['Accept'=>'text/html'])
            ->assertRedirect('/app/vinculos');
        $this->assertNotNull(NavegacaoWeb::obter($d['referencia']));
        $this->assertSame($this->revisao,session('auth_contexto_revisao'));
        $this->assertNull(ContextoWeb::obter(session('auth_contexto_ativo')));
        $this->semEfeitos();
    }

    public function test_epoca_antiga_mantem_bloqueio_mesmo_quando_contexto_esta_expirado(): void
    {
        $d=NavegacaoWeb::destino('turmas.update',['id'=>14]);$this->expirarContexto();
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>'epoca-antiga','nome'=>'Não gravar'])
            ->assertStatus(409)->assertJsonPath('acesso_alterado',true)->assertJsonMissing(['selecionar_vinculo'=>true]);
        $this->assertSame($this->revisao,session('auth_contexto_revisao'));
        $this->semEfeitos();
    }

    public function test_contexto_novo_nao_renova_acao_antiga_e_nao_executa_ref_antiga(): void
    {
        $d=NavegacaoWeb::destino('turmas.update',['id'=>14]);$nova=ContextoWeb::ativar($this->contexto(8));
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao])
            ->assertStatus(409)->assertJsonPath('acesso_alterado',true);
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$nova])->assertStatus(409);
        $this->assertSame($nova,session('auth_contexto_revisao'));$this->semEfeitos();
    }

    public function test_csrf_real_vence_recuperacao_de_contexto_em_post(): void
    {
        $d=NavegacaoWeb::destino('turmas.update',['id'=>14]);$this->expirarContexto();
        $this->withSession(['_token'=>'csrf-expiracao-ficticio']);$this->app->instance('env','local');
        try{
            $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao])->assertStatus(419);
            $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao,'_token'=>'csrf-expiracao-ficticio'])
                ->assertStatus(409)->assertJsonPath('selecionar_vinculo',true);
        }finally{$this->app->instance('env','testing');}
        $this->semEfeitos();
    }

    public function test_identidade_ausente_exige_login_em_vez_de_reutilizar_contexto(): void
    {
        $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);session()->forget('auth_token');
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao],['X-G360-Hidratar'=>'1'])
            ->assertStatus(401)->assertJsonPath('login_necessario',true);
        $this->semEfeitos();
    }

    public function test_hidratacao_html_ajax_sem_identidade_responde_401_sem_redirect_intermediario(): void
    {
        $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);session()->forget('auth_token');
        $this->post($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao],
            ['Accept'=>'text/html','X-Requested-With'=>'XMLHttpRequest','X-G360-Hidratar'=>'1'])
            ->assertStatus(401)->assertJsonPath('login_necessario',true);
        $this->semEfeitos();
    }
}
