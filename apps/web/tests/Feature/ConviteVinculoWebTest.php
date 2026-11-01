<?php
namespace Tests\Feature;

use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConviteVinculoWebTest extends TestCase
{
    private string $ctx;
    protected function setUp(): void
    {
        parent::setUp(); config(['services.api.base_url'=>'http://api.test/api']); Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Gestor','auth_email'=>'pessoa@example.test']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $this->ctx = ContextoWeb::criar(['token'=>'contexto','perfil'=>'dir_escolar','permissoes'=>['usuario.gerir','usuario.ver'], 'espaco'=>['nome'=>'Escola A']]);
    }
    private function fake(callable $f): void
    {
        Http::fake(function ($r) use ($f) {
            if (str_ends_with($r->url(),'/auth/me')) return Http::response(['data'=>['perfil'=>'dir_escolar','permissoes'=>['usuario.gerir','usuario.ver']]]);
            return $f($r);
        });
    }
    public function test_gestao_envia_vinculo_explicito_sem_dados_globais_e_permissoes_com_ponto(): void
    {
        $this->fake(function ($r) {
            $this->assertTrue($r->hasHeader('Authorization','Bearer contexto'));
            $this->assertSame('9', (string)$r['vinculo_id']);
            if ($r->method()==='PUT' && !str_contains($r->url(),'permissoes')) {
                $this->assertSame(['vinculo_id'=>9,'ativo'=>0], $r->data());
            }
            if ($r->method()==='PUT' && str_contains($r->url(),'permissoes')) {
                $this->assertSame('retirar',$r['itens'][0]['estado']);
                $this->assertSame('conceder',$r['itens'][1]['estado']);
                $this->assertSame('2027-01-01',$r['itens'][1]['valida_ate']);
            }
            return Http::response(['data'=>['itens'=>[
                ['chave'=>'prova.ver','tipo'=>'padrao','pode_alterar'=>true],
                ['chave'=>'prova.criar','tipo'=>'extra','pode_alterar'=>true]
            ]]]);
        });
        $this->put('/membros/2',['_ctx'=>$this->ctx,'vinculo_id'=>9,'ativo'=>0,'nome'=>'Não deve alterar'])->assertRedirect();
        $this->put('/membros/2/permissoes',['_ctx'=>$this->ctx,'vinculo_id'=>9,'retirar'=>['prova.ver'=>'1'],'conceder'=>['prova.criar'=>'1'],'valida_ate'=>['prova.criar'=>'2027-01-01']])->assertRedirect()->assertSessionHas('sucesso');
    }
    public function test_lista_e_edicao_exibem_vinculo_sem_campos_de_identidade_editaveis(): void
    {
        $m=['id'=>2,'nome'=>'Pessoa','email'=>'pessoa@example.test','perfil'=>'professor','situacao'=>'convidado','ativo'=>false,'vinculo_id'=>9,'espaco_nome'=>'Escola A','created_at'=>null];
        $this->fake(fn($r)=>Http::response(['data'=>str_contains($r->url(),'/usuarios/2')?$m:['usuarios'=>[$m, array_merge($m,['vinculo_id'=>10,'espaco_nome'=>'Escola B'])],'totais'=>[]]]));
        $this->get('/membros?_ctx='.$this->ctx)->assertOk()->assertSee('Vínculo #9')->assertSee('Vínculo #10')->assertSee('vinculo_id=9',false)->assertDontSee('Suspender vínculo');
        $this->get('/membros/2/editar?_ctx='.$this->ctx.'&vinculo_id=9')->assertOk()->assertSee('Gerir vínculo')->assertDontSee('name="email"',false)->assertDontSee('name="password"',false);
    }
    public function test_form_convite_e_envio_generico_usam_contexto(): void
    {
        $this->fake(function($r){
            $this->assertTrue($r->hasHeader('Authorization','Bearer contexto'));
            return Http::response(['data'=>str_ends_with($r->url(),'/opcoes')?[['id'=>5,'nome'=>'Escola A','perfis'=>['professor'],'turmas'=>[]]]:['mensagem'=>'Se a conta puder receber este vínculo, o convite será enviado.']],$r->method()==='POST'?202:200);
        });
        $this->get('/membros/convidar-vinculo?_ctx='.$this->ctx)->assertOk()->assertSee('Convidar conta existente');
        $this->post('/membros/convidar-vinculo',['_ctx'=>$this->ctx,'email'=>'alvo@example.test','espaco_id'=>5,'perfil'=>'professor'])->assertRedirect()->assertSessionHas('sucesso','Se a conta puder receber este vínculo, o convite será enviado.');
    }
    public function test_consulta_e_resposta_usam_identidade_e_referencia_sem_token_no_html_ou_sessao(): void
    {
        $token=str_repeat('x',64);
        Http::fake(function($r)use($token){
            $this->assertTrue($r->hasHeader('Authorization','Bearer identidade'));
            $this->assertSame($token,$r['token']);
            return Http::response(['data'=>['vinculo'=>'Escola A','funcao'=>'Professor','turmas'=>'3A','convidado_por'=>'Gestor']]);
        });
        $this->get('/vinculos/convite')->assertOk()->assertSee('Convite para um vínculo')->assertDontSee($token);
        $r=$this->postJson('/vinculos/convite/consultar',['convite'=>7,'token'=>$token])->assertOk()->assertDontSee($token);
        $ref=$r->json('referencia');
        $this->assertStringNotContainsString($token,json_encode(session()->all()));
        $this->post('/vinculos/convite/responder',['referencia'=>$ref,'aceitar'=>'0'])->assertRedirect('/vinculos?trocar=1')->assertSessionHas('sucesso','Convite recusado. Seus outros vínculos foram preservados.');
        $this->post('/vinculos/convite/responder',['referencia'=>$ref,'aceitar'=>'1'])->assertRedirect('/vinculos/convite');
        Http::assertSentCount(2);
    }
    public function test_referencia_inexistente_e_token_invalido_nao_sao_repassados(): void
    {
        $this->post('/vinculos/convite/responder',['referencia'=>str_repeat('a',48),'aceitar'=>true])->assertRedirect('/vinculos/convite');
        $r = $this->postJson('/vinculos/convite/consultar',['convite'=>7,'token'=>'curto']);
        $this->assertSame(422, $r->status(), $r->getContent());
        Http::assertNothingSent();
        $this->assertArrayNotHasKey('token',session()->getOldInput());
    }
    public function test_login_retorna_ao_convite(): void
    {
        Http::fake(['*'=>Http::response(['data'=>['token'=>'novo','perfil'=>null,'nome'=>'Pessoa','email'=>'p@example.test','selecionar_vinculo'=>true,'trocar_senha'=>false]])]);
        $this->post('/login',['email'=>'p@example.test','password'=>'ficticia','retorno_convite'=>1])->assertRedirect('/vinculos/convite?retorno_convite=1');
        $this->get('/login?retorno_convite=1')->assertRedirect('/vinculos/convite');
    }
    public function test_troca_obrigatoria_precede_consulta_e_aceite(): void
    {
        $this->withSession(['auth_trocar_senha'=>true]);
        $this->get('/vinculos/convite')->assertOk()->assertSee('data-trocar-senha="1"',false);
        $this->post('/vinculos/convite/consultar',['convite'=>7,'token'=>str_repeat('a',64)])->assertRedirect('/trocar-senha');
        Http::assertNothingSent();
    }
    public function test_turma_transporta_vinculo_exato_em_adicao_e_remocao(): void
    {
        $this->fake(function($r){
            $this->assertSame(9,$r['vinculo_id']);
            $this->assertTrue($r->hasHeader('Authorization','Bearer contexto'));
            return Http::response(['data'=>[]]);
        });
        $this->post('/turmas/1/professores',['_ctx'=>$this->ctx,'usuario_id'=>2,'vinculo_id'=>9])->assertRedirect();
        $this->delete('/turmas/1/professores/2',['_ctx'=>$this->ctx,'vinculo_id'=>9])->assertRedirect();
    }
}
