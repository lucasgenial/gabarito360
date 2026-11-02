<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\{Http,Cache,Crypt};
use Illuminate\Http\Client\ConnectionException;
use Tests\TestCase;

class PedidosTitularesWebTest extends TestCase
{
    private string $ref='11111111-1111-4111-8111-111111111111';
    protected function setUp(): void
    {
        parent::setUp();config(['services.api.base_url'=>'http://api.test/api','services.pedidos.interface_ativa'=>true]);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId());$this->withCredentials();
    }
    private function contexto(string $perfil='suporte'): array
    {
        return ['token'=>'token-interno','tipo_contexto'=>'interno','perfil'=>$perfil,'permissoes'=>match($perfil){
            'desenvolvedor'=>['pedido.atender','pedido.abrir'],'suporte'=>['pedido.atender'],default=>['cliente.ver']}];
    }
    private function pedido(): array
    {
        return ['referencia'=>$this->ref,'cliente_id'=>1,'espaco_id'=>1,'cliente_nome'=>'Instituição fictícia','espaco_nome'=>'Espaço fictício',
            'tipo'=>'acesso','situacao'=>'em_verificacao','versao'=>2,'identificacao_declarada'=>'Titular fictício',
            'relato'=>'<script>segredo()</script>','resultado'=>null,'resposta_referencia'=>null,'ciclo_verificacao'=>1,'verificacoes'=>[]];
    }
    private function simular(string $perfil='suporte',?callable $post=null): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(function($r) use ($perfil,$post){
            $this->assertTrue($r->hasHeader('Authorization','Bearer token-interno'));
            if(str_ends_with($r->url(),'/auth/me')) return Http::response(['data'=>$this->contexto($perfil)]);
            if($r->method()==='POST') return $post ? $post($r) : Http::response(['data'=>['referencia'=>$this->ref,'situacao'=>'em_verificacao','versao'=>3]]);
            if(str_contains($r->url(),'/opcoes')) return Http::response(['data'=>[
                'cliente'=>['id'=>1,'nome'=>'Cliente fictício'],
                'clientes'=>['data'=>[['id'=>1,'nome'=>'Cliente fictício']],'current_page'=>1,'last_page'=>2],
                'espacos'=>['data'=>[['id'=>1,'nome'=>'Espaço fictício']],'current_page'=>1,'last_page'=>1],
                'responsaveis'=>['data'=>[['id'=>2,'nome'=>'Suporte fictício','perfil'=>'suporte']],'current_page'=>1,'last_page'=>1]]]);
            if(str_contains($r->url(),$this->ref)) return Http::response(['data'=>$this->pedido()]);
            return Http::response(['data'=>['data'=>[$this->pedido()],'current_page'=>1,'last_page'=>2,'total'=>21]]);
        });
    }
    private function ticket(string $ctx,int $pos=0): string
    {
        $html=$this->get('/pedidos-titulares/'.$this->ref.'?_ctx='.$ctx)->assertOk()->getContent();
        preg_match_all('/name="envio" value="([a-zA-Z0-9]{48})"/',$html,$m);return $m[1][$pos];
    }
    private function dados(string $ctx,string $ticket): array
    {
        return ['_ctx'=>$ctx,'envio'=>$ticket,'confirmado'=>1,'versao'=>2,'situacao'=>'aguardando_informacao','motivo'=>'Motivo reservado fictício'];
    }
    public function test_listagem_detalhe_escape_contexto_e_formulario_de_abertura(): void
    {
        $this->simular();$ctx=ContextoWeb::criar($this->contexto());
        $this->get('/pedidos-titulares?_ctx='.$ctx)->assertOk()->assertSee('Pedidos de titulares')->assertSee('Próxima')->assertDontSee('Abrir protocolo');
        $this->get('/pedidos-titulares/'.$this->ref.'?_ctx='.$ctx)->assertOk()->assertSee('&lt;script&gt;segredo()',false)
            ->assertDontSee('<script>segredo()',false)->assertDontSee('token-interno')->assertSee('name="_token"',false)->assertSee('name="_ctx"',false);
        $this->simular('desenvolvedor');$dev=ContextoWeb::criar($this->contexto('desenvolvedor'));
        $this->get('/pedidos-titulares/novo?cliente_id=1&_ctx='.$dev)->assertOk()->assertSee('Registrar e atribuir')->assertSee('Suporte fictício');
    }
    public function test_financeiro_flag_e_abertura_suporte_sao_negados(): void
    {
        $this->simular('financeiro');$ctx=ContextoWeb::criar($this->contexto('financeiro'));
        $this->get('/painel?_ctx='.$ctx)->assertOk()->assertDontSee('Pedidos de titulares');
        $this->get('/pedidos-titulares?_ctx='.$ctx)->assertForbidden();
        $this->post('/pedidos-titulares',['_ctx'=>$ctx])->assertForbidden();
        $this->simular();$ctx=ContextoWeb::criar($this->contexto());
        $this->get('/pedidos-titulares/novo?_ctx='.$ctx)->assertForbidden();
        config(['services.pedidos.interface_ativa'=>false]);$this->get('/pedidos-titulares?_ctx='.$ctx)->assertNotFound();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/pedidos-titulares'));
    }
    public function test_timeout_repete_mesmo_payload_e_uuid_sem_flash_ou_novo_envio_apos_sucesso(): void
    {
        $tentativas=[];
        $this->simular(post:function($r) use (&$tentativas){$tentativas[]=$r->data();if(count($tentativas)===1)throw new ConnectionException('canario-secreto');return Http::response(['data'=>['referencia'=>$this->ref,'situacao'=>'aguardando_informacao','versao'=>3]]);});
        $ctx=ContextoWeb::criar($this->contexto());$ticket=$this->ticket($ctx);$url='/pedidos-titulares/'.$this->ref.'/triagem';
        $this->post($url,$this->dados($ctx,$ticket))->assertStatus(503)->assertSee('Repetir o mesmo envio')->assertDontSee('canario-secreto')->assertDontSee('Motivo reservado fictício')->assertSessionMissing('_old_input');
        $repetir=['_ctx'=>$ctx,'envio'=>$ticket,'confirmado'=>1,'repetir'=>1];
        $this->post($url,$repetir)->assertRedirect(route('pedidos.show',['id'=>$this->ref,'_ctx'=>$ctx]));
        $this->assertCount(2,$tentativas);$this->assertSame($tentativas[0],$tentativas[1]);$this->assertArrayNotHasKey('_ctx',$tentativas[0]);$this->assertArrayNotHasKey('envio',$tentativas[0]);
        $this->post($url,$repetir)->assertRedirect();$this->assertCount(2,$tentativas);
        $key='l4-envio:'.hash('sha256',session()->getId().'|identidade|'.$ctx.'|'.$ticket);
        $registro=json_decode(Crypt::decryptString(Cache::get($key)),true);
        $this->assertNull($registro['payload']);$this->assertSame($this->ref,$registro['recibo']['referencia']);
    }
    public function test_envio_nao_pode_mudar_payload_contexto_acao_ou_ser_repetido_expirado(): void
    {
        $posts=0;$this->simular(post:function()use(&$posts){$posts++;throw new ConnectionException('Falha fictícia');});
        $ctx=ContextoWeb::criar($this->contexto());$ticket=$this->ticket($ctx);$url='/pedidos-titulares/'.$this->ref.'/triagem';
        $d=$this->dados($ctx,$ticket);$this->post($url,$d)->assertStatus(503);
        $this->post($url,array_replace($d,['motivo'=>'Outro motivo']))->assertStatus(409);
        $outro=ContextoWeb::criar($this->contexto());
        $repetir=['_ctx'=>$outro,'envio'=>$ticket,'confirmado'=>1,'repetir'=>1];$this->post($url,$repetir)->assertStatus(419);
        $repetir['_ctx']=$ctx;$this->post('/pedidos-titulares/'.$this->ref.'/decisao',$repetir)->assertStatus(419);
        $this->travel(16)->minutes();$this->post($url,$repetir)->assertStatus(419);$this->travelBack();$this->assertSame(1,$posts);
    }
    public function test_validade_brasilia_e_campos_sensiveis_nao_vao_ao_flash(): void
    {
        $this->simular();$ctx=ContextoWeb::criar($this->contexto());$ticket=$this->ticket($ctx,1);
        $v=['_ctx'=>$ctx,'envio'=>$ticket,'confirmado'=>1,'versao'=>2,'tipo'=>'identidade','metodo'=>'conferencia_documental',
            'evidencia_referencia'=>'Evidência fictícia privada','valida_ate'=>'2026-12-01T10:30','motivo'=>'Conferência fictícia'];
        $this->post('/pedidos-titulares/'.$this->ref.'/verificacoes',$v)->assertRedirect()->assertSessionMissing('_old_input');
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['valida_ate']==='2026-12-01T13:30:00+00:00');
        $this->post('/pedidos-titulares/'.$this->ref.'/verificacoes',array_replace($v,['valida_ate'=>'invalida']))->assertStatus(422)->assertDontSee('Evidência fictícia privada')->assertSessionMissing('_old_input');
    }
    public function test_abertura_retorna_recibo_sem_expor_relato_ou_redirecionar_para_detalhe(): void
    {
        $this->simular('desenvolvedor');$ctx=ContextoWeb::criar($this->contexto('desenvolvedor'));
        $html=$this->get('/pedidos-titulares/novo?cliente_id=1&_ctx='.$ctx)->assertOk()->getContent();
        preg_match('/name="envio" value="([a-zA-Z0-9]{48})"/',$html,$m);
        $dados=['_ctx'=>$ctx,'envio'=>$m[1],'confirmado'=>1,'cliente_id'=>1,'espaco_id'=>1,'responsavel_acesso_id'=>2,
            'tipo'=>'acesso','identificacao_declarada'=>'Identificação privada fictícia','relato'=>'Relato privado fictício','motivo'=>'Abertura fictícia'];
        $this->post('/pedidos-titulares',$dados)->assertOk()->assertSee($this->ref)->assertDontSee('Relato privado fictício')->assertSessionMissing('_old_input');
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['responsavel_acesso_id']===2 && !isset($r['_ctx']));
    }
    public function test_csrf_rejeita_mutacao_sem_token(): void
    {
        $this->simular();$ctx=ContextoWeb::criar($this->contexto());
        $this->app['env']='production';
        try {$this->post('/pedidos-titulares/'.$this->ref.'/triagem',['_ctx'=>$ctx])->assertStatus(419);}
        finally {$this->app['env']='testing';}
        Http::assertNothingSent();
    }
}
