<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Support\NavegacaoHttp;

class ExportacoesTitularesWebTest extends TestCase
{
    use NavegacaoHttp;
    use \Tests\Support\ContextoSelecionado;
    private string $ref='11111111-1111-4111-8111-111111111111';
    private array $posts=[];
    private function preparar(string $perfil='suporte',bool $timeout=false): string
    {
        config(['services.api.base_url'=>'http://api.test/api','services.pedidos.interface_ativa'=>true,'services.pedidos.exportacoes'=>true]);
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId());$this->withCredentials();
        $interno=in_array($perfil,['suporte','financeiro'],true);
        $c=['acesso_interno_id'=>2,'vinculo_id'=>7,'token'=>'token-reservado','tipo_contexto'=>$interno?'interno':'vinculo','perfil'=>$perfil,'espaco'=>['nome'=>'Rede fictícia'],
            'permissoes'=>$perfil==='suporte'?['pedido.atender']:($perfil==='admin_rede'?['pedido.exportacao.autorizar']:[])];
        Http::preventStrayRequests();Http::fake(function($r)use($c,&$timeout){
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

            $this->assertTrue($r->hasHeader('Authorization','Bearer token-reservado'));
            if(str_ends_with($r->url(),'/auth/me'))return Http::response(['data'=>$c]);
            if($r->method()==='POST') {
                $this->posts[]=['url'=>$r->url(),'dados'=>$r->data()];
                if($timeout){$timeout=false;throw new \Illuminate\Http\Client\ConnectionException('Falha fictícia');}
                return Http::response(['data'=>['referencia'=>$this->ref,'situacao'=>'disponivel','versao'=>1,'expira_em'=>'2026-12-01T12:00:00+00:00']],201);
            }
            if(str_ends_with($r->url(),'/arquivo')) return Http::response('{"dados":{"matricula":"FICTICIO"}}',200,['Content-Type'=>'application/json']);
            return Http::response(['data'=>['referencia'=>$this->ref,'versao'=>5,'aluno_id'=>1,'sujeito_versao'=>1,'destinatario_acesso_id'=>2,
                'limite_validade'=>'2026-12-01T12:00:00+00:00','expira_em'=>'2026-12-01T12:00:00+00:00','campos'=>['matricula']]]);
        });
        $this->selecionarContextoFixture($c);
        return ContextoWeb::referencia($c);
    }
    private function ticket(string $ctx,?string $ref=null,int $pos=0): string
    {
        $html=$this->consultarTela('/app/exportacoes-titulares'.($ref?'?referencia='.$ref:''))->assertOk()->assertDontSee('token-reservado')->getContent();
        preg_match_all('/name="envio" value="([a-zA-Z0-9]{48})"/',$html,$m);return $m[1][$pos];
    }
    public function test_geracao_repetida_usa_mesmo_payload_e_chave_sem_token_no_html(): void
    {
        $ctx=$this->preparar('suporte',true);$t=$this->ticket($ctx);
        $d=['envio'=>$t,'referencia'=>$this->ref,'versao'=>1,'confirmado'=>1,'_token'=>session()->token(),'_revisao'=>session('auth_contexto_revisao')];
        $this->alterarRecurso('POST', '/app/exportacoes-titulares/gerar',$d)->assertStatus(503)->assertSee('Repetir o mesmo envio');
        $this->alterarRecurso('POST', '/app/exportacoes-titulares/gerar',array_diff_key($d,['versao'=>true])+['repetir'=>1])->assertOk()->assertSee('Arquivo gerado');
        $this->assertSame($this->posts[0],$this->posts[1]);
        $this->assertArrayNotHasKey('_revisao',$this->posts[0]['dados']);
        $this->assertArrayNotHasKey('_token',$this->posts[0]['dados']);
        $this->assertArrayNotHasKey('referencia_alvo',$this->posts[0]['dados']);
        $this->assertStringEndsWith('/exportacoes/'.$this->ref,$this->posts[0]['url']);
        $this->alterarRecurso('POST', '/app/exportacoes-titulares/gerar',array_replace($d,['versao'=>2]))->assertConflict();
    }
    public function test_administrador_confirma_campos_e_prazo_utc_e_nao_baixa(): void
    {
        $ctx=$this->preparar('admin_rede');$t=$this->ticket($ctx,$this->ref);
        $this->alterarRecurso('POST', '/app/exportacoes-titulares/conceder',['envio'=>$t,'referencia'=>$this->ref,'confirmado'=>1,
            'versao'=>5,'aluno_id'=>1,'sujeito_versao'=>1,'destinatario_acesso_id'=>2,'campos'=>['nome','matricula'],
            'valida_ate'=>'2026-12-01T08:00','motivo'=>'Motivo fictício','evidencia_referencia'=>'Evidência fictícia'])->assertOk()->assertSee('Autorização registrada');
        $this->assertSame('2026-12-01T11:00:00+00:00',$this->posts[0]['dados']['valida_ate']);
        $this->assertSame(['matricula','nome'],$this->posts[0]['dados']['campos']);
        $this->consultarTela('/app/exportacoes-titulares/'.$this->ref.'/arquivo')->assertForbidden();
        $this->assertNull(session()->getOldInput('motivo'));
    }
    public function test_download_proxy_autenticado_sem_cache_e_flag(): void
    {
        $ctx=$this->preparar();$this->ticket($ctx,$this->ref);
        $this->consultarTela('/app/exportacoes-titulares/'.$this->ref.'/arquivo')->assertOk()
            ->assertHeader('Content-Disposition','attachment; filename="exportacao-'.$this->ref.'.json"')->assertHeader('Cache-Control','no-store, private')->assertDontSee('token-reservado');
        config(['services.pedidos.exportacoes'=>false]);$this->consultarTela('/app/exportacoes-titulares')->assertNotFound();
    }
    public function test_financeiro_e_diretor_nao_entram(): void
    {
        $ctx=$this->preparar('financeiro');$this->consultarTela('/app/exportacoes-titulares')->assertForbidden();
    }
    public function test_download_post_usa_csrf_e_revisao_do_formulario_ssr_sem_header(): void
    {
        $this->preparar();
        $html=$this->consultarTela('/app/exportacoes-titulares?referencia='.$this->ref)->assertOk()->getContent();
        $dom=new \DOMDocument();@$dom->loadHTML($html);$xp=new \DOMXPath($dom);$form=$xp->query('//form[contains(@class,"arquivo-form")]')->item(0);$this->assertNotNull($form);
        $d=[];foreach($xp->query('.//input[@type="hidden"]',$form) as $i)$d[$i->getAttribute('name')]=$i->getAttribute('value');
        foreach(['_token','_revisao','_recurso'] as $nome)$this->assertNotEmpty($d[$nome]);
        $destino=json_decode($form->getAttribute('data-recurso'),true,512,JSON_THROW_ON_ERROR);$url=$form->getAttribute('action');
        $this->flushHeaders();$this->app->instance('env','local');
        $this->get($destino['pagina'])->assertStatus(409);
        $this->post($url,array_diff_key($d,['_token'=>true]))->assertStatus(419);
        Http::assertNotSent(fn($r)=>str_ends_with($r->url(),'/arquivo'));
        $this->post($url,$d)->assertOk()->assertHeader('Content-Disposition','attachment; filename="exportacao-'.$this->ref.'.json"')->assertHeader('Cache-Control','no-store, private');
        Http::assertSent(fn($r)=>$r->method()==='GET' && str_ends_with($r->url(),'/arquivo') && $r->data()===[]);
        $this->post($url,array_replace($d,['_revisao'=>'revisao-antiga']))->assertStatus(409);
    }

    public function test_envio_e_repeticao_nao_aceitam_campos_de_negocio_desconhecidos(): void
    {
        $ctx=$this->preparar();$t=$this->ticket($ctx);
        $d=['envio'=>$t,'referencia'=>$this->ref,'versao'=>1,'confirmado'=>1,'_token'=>session()->token(),'_revisao'=>session('auth_contexto_revisao')];
        $this->alterarRecurso('POST', '/app/exportacoes-titulares/gerar',$d+['campo_inesperado'=>'nao-enviar'])->assertStatus(422);
        $this->alterarRecurso('POST', '/app/exportacoes-titulares/gerar',array_diff_key($d,['versao'=>true])+['repetir'=>1,'campo_inesperado'=>'nao-enviar'])->assertStatus(422);
        $this->assertSame([],$this->posts);
    }

}
