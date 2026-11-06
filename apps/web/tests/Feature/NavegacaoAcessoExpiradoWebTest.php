<?php
namespace Tests\Feature;

use App\Support\{ContextoWeb,NavegacaoWeb};
use Illuminate\Support\Facades\{Http,Cache};
use Tests\TestCase;

/** O vínculo ativo acompanha a sessão; falha de integridade exige novo login. */
final class NavegacaoAcessoExpiradoWebTest extends TestCase
{
    private string $revisao;
    private int $executadas=0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        $this->withSession(['auth_token'=>'identidade-expiracao-ficticia','auth_vinculos'=>true]);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $this->revisao=ContextoWeb::ativar($this->contexto());
        Http::preventStrayRequests();
        Http::fake(['*/v1/auth/logout'=>Http::response(['data'=>null],200)]);
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
        $this->assertSame(0,$this->executadas);
        foreach(Http::recorded() as [$request,$response]) {
            $this->assertSame('POST',$request->method());
            $this->assertSame('/api/v1/auth/logout',parse_url($request->url(),PHP_URL_PATH));
            $this->assertNull(parse_url($request->url(),PHP_URL_QUERY));
            $this->assertContains($request->header('Authorization')[0]??'',[
                'Bearer identidade-expiracao-ficticia','Bearer identidade-diferente-ficticia']);
        }
        $this->assertLessThanOrEqual(1,Http::recorded()->count());
    }

    public function test_get_painel_e_listas_com_contexto_perdido_encerram_sessao(): void
    {
        $this->expirarContexto();
        foreach(['/app/painel','/app/turmas','/app/provas','/app/alunos'] as $path)
            $this->get($path)->assertRedirect('/login')->assertDontSee('controller_executado');
        $this->assertNull(session('auth_contexto_revisao'));$this->assertNull(session('auth_token'));
        $this->assertNull(session('auth_token'));
        $this->semEfeitos();
    }

    public function test_hidratacao_com_ref_ainda_viva_e_contexto_expirado_exige_login_sem_executar(): void
    {
        $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);$this->expirarContexto();
        $this->assertNotNull(NavegacaoWeb::obter($d['referencia']));
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao],['X-G360-Hidratar'=>'1'])
            ->assertStatus(401)->assertJsonPath('login_necessario',true)
            ->assertDontSee('controller_executado');
        $this->assertNull(session('auth_contexto_revisao'));$this->assertNull(session('auth_token'));
        $this->semEfeitos();
    }

    public function test_mutacao_nativa_com_contexto_expirado_nao_reaplica_nem_seleciona_automaticamente(): void
    {
        $d=NavegacaoWeb::destino('turmas.update',['id'=>14]);$this->expirarContexto();
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao,'nome'=>'Não gravar'])
            ->assertStatus(401)->assertJsonPath('login_necessario',true);
        $this->assertNull(session('auth_contexto_revisao'));$this->assertNull(session('auth_token'));
        $this->semEfeitos();
    }

    public function test_contexto_e_referencia_expirados_juntos_exigem_novo_login(): void
    {
        $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);$this->expirarContexto();
        $this->travel((int)config('session.lifetime')+1)->minutes();
        try{
            $this->assertNull(NavegacaoWeb::obter($d['referencia']));
            $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao],['X-G360-Hidratar'=>'1'])
                ->assertStatus(401)->assertJsonPath('login_necessario',true);
        }finally{$this->travelBack();}
        $this->assertNull(session('auth_contexto_revisao'));$this->assertNull(session('auth_token'));
        $this->semEfeitos();
    }

    public function test_formulario_nativo_sem_contexto_vai_ao_login_sem_executar(): void
    {
        $d=NavegacaoWeb::destino('turmas.update',['id'=>14]);$this->expirarContexto();
        $this->post($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao,
            '_token'=>csrf_token(),'_method'=>'PUT','nome'=>'Não gravar'],['Accept'=>'text/html'])
            ->assertRedirect('/login');
        $this->assertNull(NavegacaoWeb::obter($d['referencia']));
        $this->assertNull(session('auth_contexto_revisao'));$this->assertNull(session('auth_token'));
        $this->assertNull(ContextoWeb::obter(session('auth_contexto_ativo')));
        $this->semEfeitos();
    }

    public function test_epoca_antiga_mantem_bloqueio_mesmo_quando_contexto_esta_expirado(): void
    {
        $d=NavegacaoWeb::destino('turmas.update',['id'=>14]);$this->expirarContexto();
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>'epoca-antiga','nome'=>'Não gravar'])
            ->assertStatus(409)->assertJsonPath('acesso_alterado',true)->assertJsonMissing(['selecionar_vinculo'=>true]);
        $this->assertSame($this->revisao,session('auth_contexto_revisao'));$this->assertSame('identidade-expiracao-ficticia',session('auth_token'));
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
            Http::assertNothingSent();$this->assertSame('identidade-expiracao-ficticia',session('auth_token'));
            $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao,'_token'=>'csrf-expiracao-ficticio'])
                ->assertStatus(401)->assertJsonPath('login_necessario',true);
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
    public function test_cache_externo_expira_mas_payload_ativo_permanece_sem_relogio_independente(): void
    {
        $handle=session('auth_contexto_ativo');$cipher=session('auth_contexto_dados');
        $this->assertIsString($cipher);$this->assertStringNotContainsString('contexto-expiracao-ficticio',$cipher);
        Cache::flush();
        $this->travel((int)config('session.lifetime')+1)->minutes();
        try{
            // Não simula sessão expirada: o Store permanece vivo. Só o cache/relógio externo avança.
            $this->assertSame($this->contexto(),ContextoWeb::obter($handle));
            $this->get('/app/painel')->assertOk();
            $this->assertSame($handle,session('auth_contexto_ativo'));
            $this->assertSame($this->revisao,session('auth_contexto_revisao'));
        }finally{$this->travelBack();}
        Http::assertNothingSent();
    }

    public function test_payload_corrompido_nao_faz_fallback_ao_cache_valido(): void
    {
        $handle=session('auth_contexto_ativo');session(['auth_contexto_dados'=>'ciphertext-corrompido']);
        $this->assertNull(ContextoWeb::obter($handle));
        $this->get('/app/painel')->assertRedirect('/login');
        $this->assertNull(session('auth_token'));$this->assertNull(session('auth_contexto_dados'));
        $this->semEfeitos();
    }

    public function test_ciphertext_de_outra_identidade_na_mesma_sessao_nao_autoriza(): void
    {
        $handle=session('auth_contexto_ativo');session(['auth_token'=>'identidade-diferente-ficticia']);
        $this->assertNull(ContextoWeb::obter($handle));
        $this->get('/app/painel')->assertRedirect('/login');$this->assertNull(session('auth_token'));
        $this->semEfeitos();
    }

    public function test_ciphertext_de_outro_handle_nao_autoriza(): void
    {
        session(['auth_contexto_ativo'=>str_repeat('X',48)]);
        $this->get('/app/painel')->assertRedirect('/login');$this->assertNull(session('auth_token'));
        $this->semEfeitos();
    }

    public function test_sessao_legada_valida_migra_uma_vez_e_sobrevive_perda_do_cache(): void
    {
        $handle=session('auth_contexto_ativo');session()->forget(['auth_contexto_dados','auth_contexto_versao']);
        $this->get('/app/painel')->assertOk();$this->assertIsString(session('auth_contexto_dados'));
        Cache::flush();$this->assertSame($this->contexto(),ContextoWeb::obter($handle));
        Http::assertNothingSent();
    }

    public function test_login_ainda_sem_selecao_permite_escolha_sem_encerrar_identidade(): void
    {
        ContextoWeb::limpar();
        $this->get('/app/painel')->assertRedirect('/app/vinculos');
        $this->assertSame('identidade-expiracao-ficticia',session('auth_token'));$this->semEfeitos();
    }

    public function test_payload_de_sessao_nova_ausente_nao_ressuscita_copia_em_cache(): void
    {
        $handle=session('auth_contexto_ativo');$this->assertNotNull(session('auth_contexto_versao'));
        session()->forget('auth_contexto_dados');
        $this->assertNull(ContextoWeb::obter($handle));
        $this->get('/app/painel')->assertRedirect('/login');$this->assertNull(session('auth_token'));
        $this->semEfeitos();
    }

    public function test_payload_de_outra_sessao_nao_autoriza_mesma_identidade_e_handle(): void
    {
        $handle=session('auth_contexto_ativo');session()->setId(str_repeat('s',40));
        $this->assertNull(ContextoWeb::obter($handle));
        Http::assertNothingSent();
    }

    public function test_falha_de_rede_ao_revogar_nao_preserva_autoridade_local(): void
    {
        Http::fake(['*/v1/auth/logout'=>Http::failedConnection()]);$this->expirarContexto();
        $tokenAntigo=csrf_token();
        $this->get('/app/painel')->assertRedirect('/login');
        $this->assertNull(session('auth_token'));$this->assertNull(session('auth_contexto_ativo'));
        $this->assertNull(ContextoWeb::identidade());$this->assertNull(ContextoWeb::atual());
        $this->assertNotSame($tokenAntigo,csrf_token());$this->assertSame(0,$this->executadas);
    }

    public function test_resposta_500_ao_revogar_nao_preserva_autoridade_local(): void
    {
        Http::fake(['*/v1/auth/logout'=>Http::response(['message'=>'indisponível fictício'],500)]);
        $this->expirarContexto();$this->get('/app/painel')->assertRedirect('/login');
        $this->assertNull(session('auth_token'));$this->assertNull(ContextoWeb::identidade());
        $this->semEfeitos();
    }

    public function test_api_recusa_contexto_ativo_exige_login_e_revoga_parent_nao_filho(): void
    {
        Http::fake(['*/v1/auth/me'=>Http::response(['message'=>'revogado'],403),
            '*/v1/auth/logout'=>Http::response(['data'=>null],200)]);
        $d=NavegacaoWeb::destino('perfil');
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao],['X-G360-Hidratar'=>'1'])
            ->assertStatus(401)->assertJsonPath('login_necessario',true);
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/v1/auth/me')
            &&$r->hasHeader('Authorization','Bearer contexto-expiracao-ficticio'));
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/v1/auth/logout')
            &&$r->hasHeader('Authorization','Bearer identidade-expiracao-ficticia'));
        Http::assertSentCount(2);$this->assertNull(session('auth_token'));
    }

    public function test_ref_de_recurso_expirada_nao_encerra_contexto_ativo_valido(): void
    {
        $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        $this->travel((int)config('session.lifetime')+1)->minutes();
        try{
            $this->assertNull(NavegacaoWeb::obter($d['referencia']));
            $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao])
                ->assertStatus(409)->assertHeaderMissing('Location')->assertDontSee('controller_executado');
            $this->assertSame($this->contexto(),ContextoWeb::obter(session('auth_contexto_ativo')));
            $this->assertSame('identidade-expiracao-ficticia',session('auth_token'));
        }finally{$this->travelBack();}
        $this->semEfeitos();Http::assertNothingSent();
    }

    public function test_indisponibilidade_da_api_nao_e_expiracao_nem_revoga_sessao(): void
    {
        Http::fake(['*/v1/auth/me'=>Http::response(['message'=>'temporário'],503)]);
        $d=NavegacaoWeb::destino('perfil');
        $this->postJson($d['url'],['_recurso'=>$d['referencia'],'_revisao'=>$this->revisao],['X-G360-Hidratar'=>'1'])
            ->assertStatus(503);
        $this->assertSame('identidade-expiracao-ficticia',session('auth_token'));
        $this->assertSame($this->revisao,session('auth_contexto_revisao'));
        Http::assertSentCount(1);Http::assertNotSent(fn($r)=>str_ends_with($r->url(),'/logout'));
    }

    public function test_storage_cookie_nao_recebe_novo_payload_de_autoridade(): void
    {
        config(['session.driver'=>'cookie']);
        try {ContextoWeb::ativar($this->contexto());$this->fail('Driver cookie deveria ser recusado.');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(503,$e->getStatusCode());}
        Http::assertNothingSent();
    }

    public function test_handle_perdido_com_payload_ativo_nao_e_primeiro_login(): void
    {
        session()->forget('auth_contexto_ativo');
        $this->assertTrue(ContextoWeb::temSelecao());
        $this->get('/app/painel')->assertRedirect('/login');
        $this->assertNull(session('auth_token'));$this->semEfeitos();
    }

}
