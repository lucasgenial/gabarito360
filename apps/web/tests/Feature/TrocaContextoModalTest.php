<?php
namespace Tests\Feature;

use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrocaContextoModalTest extends TestCase
{
    private array $atual;

    private function preparar(bool $interno=false): string
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_internos'=>$interno,'auth_nome'=>'Teste']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $this->atual=['token'=>'contexto-atual-secreto','vinculo_id'=>11,'perfil'=>'professor','permissoes'=>['prova.ver','gabarito.ver'],
            'espaco'=>['id'=>1,'tipo'=>'escola','nome'=>'Escola A']];
        if ($interno) $this->atual=['token'=>'contexto-atual-secreto','tipo_contexto'=>'interno','acesso_interno_id'=>1,'perfil'=>'suporte','permissoes'=>[]];
        return ContextoWeb::criar($this->atual);
    }

    private function opcoes(): array
    {
        return [
            ['id'=>11,'perfil'=>'professor','espaco'=>['id'=>1,'tipo'=>'escola','nome'=>'Escola A']],
            ['id'=>22,'perfil'=>'professor','espaco'=>['id'=>2,'tipo'=>'escola','nome'=>'Escola <teste> B']],
        ];
    }

    private function api(array $vinculos,array $internos=[],int $estado=200): void
    {
        Http::fake(function($r)use($vinculos,$internos,$estado){
            if (str_ends_with($r->url(),'/auth/me')) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer contexto-atual-secreto'));
                return Http::response(['data'=>$this->atual]);
            }
            $this->assertTrue($r->hasHeader('Authorization','Bearer identidade'));
            if (str_ends_with($r->url(),'/auth/vinculos')) return Http::response(['data'=>$vinculos],$estado);
            if (str_ends_with($r->url(),'/auth/acessos-internos')) return Http::response(['data'=>$internos]);
            $this->fail('Consulta inesperada.');
        });
    }

    public function test_vinculo_unico_nao_renderiza_icone_nem_modal(): void
    {
        $ctx=$this->preparar();$this->api([$this->opcoes()[0]]);
        $this->get('/acessos/'.ContextoWeb::referencia($this->atual).'/referencia-visual')->assertOk()->assertSee('Meu espaço')
            ->assertDontSee('id="contexto-trocar"',false)->assertDontSee('id="troca-contexto-modal"',false);
        Http::assertSentCount(2);
    }

    public function test_modal_preserva_identidade_atual_csrf_e_nao_expoe_tokens(): void
    {
        $ctx=$this->preparar();$this->api($this->opcoes());
        $r=$this->get('/acessos/'.ContextoWeb::referencia($this->atual).'/referencia-visual')->assertOk()->assertSee('id="contexto-trocar"',false)
            ->assertSee('aria-haspopup="dialog"',false)->assertSee('id="troca-contexto-modal"',false)
            ->assertSee('Atual')->assertSee('Escola &lt;teste&gt; B',false)->assertSee('data-sem-contexto',false)
            ->assertDontSee('contexto-atual-secreto')->assertDontSee('Bearer identidade');
        $doc=new \DOMDocument();@$doc->loadHTML('<?xml encoding="utf-8" ?>'.$r->getContent());$xp=new \DOMXPath($doc);
        $forms=$xp->query('//dialog[@id="troca-contexto-modal"]//form');
        $this->assertSame(1,$forms->length);
        $this->assertSame('22',$xp->query('.//input[@name="vinculo_id"]',$forms->item(0))->item(0)->getAttribute('value'));
        $this->assertSame(1,$xp->query('.//input[@name="_token"]',$forms->item(0))->length);
        Http::assertNotSent(fn($r)=>$r->method()==='POST');
    }

    public function test_acesso_interno_unico_e_alternativa_escolar_sao_contados_separadamente(): void
    {
        $ctx=$this->preparar(true);$this->api([],[['id'=>1,'perfil'=>'suporte']]);
        $this->get('/acessos/'.ContextoWeb::referencia($this->atual).'/painel')->assertOk()->assertDontSee('id="contexto-trocar"',false);
        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->api([$this->opcoes()[0]],[['id'=>1,'perfil'=>'suporte']]);
        $this->get('/acessos/'.ContextoWeb::referencia($this->atual).'/painel')->assertOk()->assertSee('Trocar acesso')->assertSee('Equipe Gabarito360')->assertSee('Escola A');
    }

    public function test_falha_de_consulta_nao_inventa_alternativas_e_apresenta_orientacao(): void
    {
        $ctx=$this->preparar();$this->api([],[],503);
        $this->get('/acessos/'.ContextoWeb::referencia($this->atual).'/referencia-visual')->assertOk()->assertDontSee('id="contexto-trocar"',false)
            ->assertSee('Não foi possível verificar outros acessos.');
    }

    public function test_selecao_json_preserva_contexto_antigo_e_nao_devolve_token_ao_browser(): void
    {
        $ctx=$this->preparar();
        Http::fake(function($r){
            $this->assertTrue($r->hasHeader('Authorization','Bearer identidade'));
            return Http::response(['data'=>['token'=>'novo-token-secreto','vinculo_id'=>22,'perfil'=>'professor','permissoes'=>[],
                'espaco'=>['id'=>2,'tipo'=>'escola','nome'=>'Escola B']]],201);
        });
        $r=$this->postJson('/vinculos',['vinculo_id'=>22])->assertOk()->assertDontSee('novo-token-secreto');
        $this->assertSame('/acessos/vinculo-22/painel',parse_url($r->json('url'),PHP_URL_PATH));
        $this->assertNull(parse_url($r->json('url'),PHP_URL_QUERY));
        $this->assertStringNotContainsString($ctx,$r->json('url'));
        $this->assertSame('contexto-atual-secreto',ContextoWeb::obter($ctx)['token']);
        $this->assertSame('novo-token-secreto',ContextoWeb::resolverReferencia('vinculo-22')['dados']['token']);
        $this->assertSame('identidade',session('auth_token'));
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*'=>Http::response(['message'=>'revogado'],403)]);
        $this->postJson('/vinculos',['vinculo_id'=>22])->assertStatus(409)->assertJsonPath('message','Este vínculo não está disponível. Atualize a página para conferir seus acessos.');
        $this->assertSame('contexto-atual-secreto',ContextoWeb::obter($ctx)['token']);
    }

    public function test_selecao_interna_json_revalida_sem_sobrescrever_o_acesso_da_outra_aba(): void
    {
        $ctx=$this->preparar(true);
        Http::fake(function($r){
            $this->assertTrue($r->hasHeader('Authorization','Bearer identidade'));
            $this->assertSame(2,$r['acesso_interno_id']);
            return Http::response(['data'=>['token'=>'interno-novo-secreto','tipo_contexto'=>'interno','acesso_interno_id'=>2,
                'perfil'=>'desenvolvedor','permissoes'=>[]]],201);
        });
        $r=$this->postJson('/vinculos/interno',['acesso_interno_id'=>2])->assertOk()->assertDontSee('interno-novo-secreto');
        $this->assertSame('/acessos/interno-2/painel',parse_url($r->json('url'),PHP_URL_PATH));
        $this->assertNull(parse_url($r->json('url'),PHP_URL_QUERY));
        $this->assertStringNotContainsString($ctx,$r->json('url'));
        $this->assertSame('interno-novo-secreto',ContextoWeb::resolverReferencia('interno-2')['dados']['token']);
        $this->assertSame('contexto-atual-secreto',ContextoWeb::obter($ctx)['token']);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*'=>Http::response([],403)]);
        $this->postJson('/vinculos/interno',['acesso_interno_id'=>2])->assertStatus(409);
        $this->assertSame('contexto-atual-secreto',ContextoWeb::obter($ctx)['token']);
    }

    public function test_falha_de_servico_nao_afirma_que_troca_foi_recusada(): void
    {
        $ctx=$this->preparar();
        Http::fake(['*'=>Http::response([],503)]);
        $this->postJson('/vinculos',['vinculo_id'=>22])->assertStatus(503)
            ->assertJsonPath('message','Não foi possível confirmar a troca. Atualize a página antes de tentar novamente.');
        $this->assertSame('contexto-atual-secreto',ContextoWeb::obter($ctx)['token']);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(fn()=>throw new \Illuminate\Http\Client\ConnectionException('Simulação local'));
        $this->postJson('/vinculos',['vinculo_id'=>22])->assertStatus(503)
            ->assertJsonPath('message','Não foi possível confirmar a troca. Atualize a página antes de tentar novamente.');
        $this->assertSame('contexto-atual-secreto',ContextoWeb::obter($ctx)['token']);
    }
}
