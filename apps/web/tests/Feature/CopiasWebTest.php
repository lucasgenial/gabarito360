<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CopiasWebTest extends TestCase
{
    private function preparar(string $perfil='dir_escolar'): array
    {
        config(['services.api.base_url'=>'http://api.test/api','services.copias.interface_ativa'=>true]); Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Fictício']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $dados=['token'=>'contexto-copia','tipo_contexto'=>'vinculo','perfil'=>$perfil,'espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola fictícia'],
            'permissoes'=>['copia.autorizar_origem','copia.autorizar_destino']];
        return [ContextoWeb::criar($dados),$dados];
    }
    private function dados(): array
    {
        return ['espacos'=>[['id'=>5,'nome'=>'Escola <script>segredo</script>']],'espaco_id'=>5,'lado'=>'origem','lados'=>['origem','destino'],
            'lista'=>['data'=>[['id'=>'ficticio','finalidade'=>'resultados_identificados','estado'=>'aguardando_recibo','selecionados'=>20,'publicados'=>20,
                'auditoria_pendente'=>true,'criado_em'=>'2026-11-02 05:00:00']],'total'=>21,'current_page'=>1,'last_page'=>2]];
    }
    public function test_tela_menu_filtros_contexto_e_escape(): void
    {
        [$ctx,$me]=$this->preparar();
        Http::fake(function($r) use($me) {
            $this->assertTrue($r->hasHeader('Authorization','Bearer contexto-copia'));
            return Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$this->dados()]);
        });
        $r=$this->get('/copias?_ctx='.$ctx.'&lado=origem&espaco_id=5');
        $r->assertOk()->assertSee('Histórico de resultados')->assertSee('Publicado; confirmação dos registros pendente')
            ->assertSee('20 de 20')->assertSee('Atualizar acompanhamento')->assertSee('name="_ctx"',false)->assertSee($ctx)
            ->assertDontSee('<script>segredo</script>',false)->assertSee('&lt;script&gt;',false)->assertDontSee('Autorizar transferência');
        Http::assertSent(fn($r)=>str_contains($r->url(),'/v1/copias') && $r['lado']==='origem' && $r['espaco_id']==5 && !isset($r['_ctx']));
        Http::assertNotSent(fn($r)=>$r->method()!=='GET');
    }
    public function test_papel_negado_mesmo_com_capacidade_e_flag_desligada(): void
    {
        [$ctx,$me]=$this->preparar('professor'); Http::fake(['*'=>Http::response(['data'=>$me])]);
        $this->get('/copias?_ctx='.$ctx)->assertForbidden(); Http::assertNotSent(fn($r)=>str_contains($r->url(),'/v1/copias'));
        config(['services.copias.interface_ativa'=>false]); $this->get('/copias?_ctx='.$ctx)->assertNotFound();
    }
    public function test_indisponibilidade_nao_vira_lista_vazia_nem_expoe_erro(): void
    {
        [$ctx,$me]=$this->preparar();
        Http::fake(fn($r)=>str_ends_with($r->url(),'/auth/me')?Http::response(['data'=>$me]):Http::response(['message'=>'SEGREDO-INTERNO'],500));
        $this->get('/copias?_ctx='.$ctx)->assertStatus(503)->assertDontSee('SEGREDO-INTERNO')->assertDontSee('Nenhuma solicitação encontrada');
        Http::fake(function($r) use($me) {
            if (str_ends_with($r->url(),'/auth/me')) return Http::response(['data'=>$me]);
            throw new \Illuminate\Http\Client\ConnectionException('SEGREDO-CONEXAO');
        });
        $this->get('/copias?_ctx='.$ctx)->assertStatus(503)->assertDontSee('SEGREDO-CONEXAO');
    }
    public function test_estado_vazio_e_direcao_destino(): void
    {
        [$ctx,$me]=$this->preparar(); $dados=$this->dados(); $dados['lado']='destino';
        $dados['lista']=['data'=>[],'total'=>0,'current_page'=>1,'last_page'=>1];
        Http::fake(fn($r)=>Http::response(['data'=>str_ends_with($r->url(),'/auth/me')?$me:$dados]));
        $this->get('/copias?_ctx='.$ctx.'&lado=destino')->assertOk()->assertSee('Nenhuma solicitação encontrada')->assertSee('registra sua decisão');
    }
}
