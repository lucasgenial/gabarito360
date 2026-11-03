<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExportacoesTitularesWebTest extends TestCase
{
    private string $ref='11111111-1111-4111-8111-111111111111';
    private array $posts=[];
    private function preparar(string $perfil='suporte',bool $timeout=false): string
    {
        config(['services.api.base_url'=>'http://api.test/api','services.pedidos.interface_ativa'=>true,'services.pedidos.exportacoes'=>true]);
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId());$this->withCredentials();
        $interno=in_array($perfil,['suporte','financeiro'],true);
        $c=['token'=>'token-reservado','tipo_contexto'=>$interno?'interno':'vinculo','perfil'=>$perfil,'espaco'=>['nome'=>'Rede fictícia'],
            'permissoes'=>$perfil==='suporte'?['pedido.atender']:($perfil==='admin_rede'?['pedido.exportacao.autorizar']:[])];
        Http::preventStrayRequests();Http::fake(function($r)use($c,&$timeout){
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
        return ContextoWeb::criar($c);
    }
    private function ticket(string $ctx,?string $ref=null,int $pos=0): string
    {
        $html=$this->get('/exportacoes-titulares?_ctx='.$ctx.($ref?'&referencia='.$ref:''))->assertOk()->assertDontSee('token-reservado')->getContent();
        preg_match_all('/name="envio" value="([a-zA-Z0-9]{48})"/',$html,$m);return $m[1][$pos];
    }
    public function test_geracao_repetida_usa_mesmo_payload_e_chave_sem_token_no_html(): void
    {
        $ctx=$this->preparar('suporte',true);$t=$this->ticket($ctx);
        $d=['_ctx'=>$ctx,'envio'=>$t,'referencia'=>$this->ref,'versao'=>1,'confirmado'=>1];
        $this->post('/exportacoes-titulares/gerar',$d)->assertStatus(503)->assertSee('Repetir o mesmo envio');
        $this->post('/exportacoes-titulares/gerar',array_diff_key($d,['versao'=>true])+['repetir'=>1])->assertOk()->assertSee('Arquivo gerado');
        $this->assertSame($this->posts[0],$this->posts[1]);
        $this->assertArrayNotHasKey('referencia_alvo',$this->posts[0]['dados']);
        $this->assertStringEndsWith('/exportacoes/'.$this->ref,$this->posts[0]['url']);
        $this->post('/exportacoes-titulares/gerar',array_replace($d,['versao'=>2]))->assertConflict();
    }
    public function test_administrador_confirma_campos_e_prazo_utc_e_nao_baixa(): void
    {
        $ctx=$this->preparar('admin_rede');$t=$this->ticket($ctx,$this->ref);
        $this->post('/exportacoes-titulares/conceder',['_ctx'=>$ctx,'envio'=>$t,'referencia'=>$this->ref,'confirmado'=>1,
            'versao'=>5,'aluno_id'=>1,'sujeito_versao'=>1,'destinatario_acesso_id'=>2,'campos'=>['nome','matricula'],
            'valida_ate'=>'2026-12-01T08:00','motivo'=>'Motivo fictício','evidencia_referencia'=>'Evidência fictícia'])->assertOk()->assertSee('Autorização registrada');
        $this->assertSame('2026-12-01T11:00:00+00:00',$this->posts[0]['dados']['valida_ate']);
        $this->assertSame(['matricula','nome'],$this->posts[0]['dados']['campos']);
        $this->get('/exportacoes-titulares/'.$this->ref.'/arquivo?_ctx='.$ctx)->assertForbidden();
        $this->assertNull(session()->getOldInput('motivo'));
    }
    public function test_download_proxy_autenticado_sem_cache_e_flag(): void
    {
        $ctx=$this->preparar();$this->ticket($ctx,$this->ref);
        $this->get('/exportacoes-titulares/'.$this->ref.'/arquivo?_ctx='.$ctx)->assertOk()
            ->assertHeader('Content-Disposition','attachment; filename="exportacao-'.$this->ref.'.json"')->assertHeader('Cache-Control','no-store, private')->assertDontSee('token-reservado');
        config(['services.pedidos.exportacoes'=>false]);$this->get('/exportacoes-titulares?_ctx='.$ctx)->assertNotFound();
    }
    public function test_financeiro_e_diretor_nao_entram(): void
    {
        $ctx=$this->preparar('financeiro');$this->get('/exportacoes-titulares?_ctx='.$ctx)->assertForbidden();
    }
}
