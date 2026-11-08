<?php
namespace Tests\Feature;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class EstadoTelaWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug'=>false]);
        \Illuminate\Support\Facades\View::share('errors',new \Illuminate\Support\ViewErrorBag());
        app('router')->any('/ui1-erro/{codigo}',fn($codigo)=>throw new HttpException((int)$codigo,'segredo-interno-nao-exibir',null,['Retry-After'=>'17']));
    }

    public function test_erros_html_preservam_status_cabecalho_e_nao_exibem_excecao(): void
    {
        foreach([401,403,404,419,429,500,503] as $codigo){
            $r=$this->get('/ui1-erro/'.$codigo,['Accept'=>'text/html']);
            $r->assertStatus($codigo)->assertHeader('Retry-After','17')
                ->assertSee('estado-pagina')->assertSee('estado-marca')
                ->assertDontSee('segredo-interno-nao-exibir')->assertDontSee('estado-conteudo--carregando');
            if(in_array($codigo,[401,419],true))$r->assertSee('href="/login"',false);
            if($codigo===429)$r->assertSee('Aguarde 17 segundos');
        }
    }

    public function test_falha_de_post_nao_oferece_reenvio_e_json_nao_vira_html(): void
    {
        $this->post('/ui1-erro/500',[],['Accept'=>'text/html'])->assertStatus(500)
            ->assertSee('Confira se a operação foi concluída')->assertDontSee('Tentar novamente')
            ->assertDontSee('<form',false);
        $this->getJson('/ui1-erro/503')->assertStatus(503)->assertHeader('Content-Type','application/json')
            ->assertDontSee('<!doctype html>',false)->assertDontSee('estado-pagina');
    }

    public function test_carregamento_e_ponte_usam_mesmo_estado_sem_card_com_fallback_sem_js(): void
    {
        $this->view('navegacao.estrutura',['meta'=>['consulta'=>'app/turmas/consultar','pagina'=>'app/turmas/detalhes','lista'=>'/app/turmas','obrigatorios'=>['id']]])
            ->assertSee('Abrindo tela…')->assertSee('role="status"',false)->assertSee('aria-busy="true"',false)
            ->assertSee('estado-sem-javascript.css')->assertSee('Ative o JavaScript')->assertDontSee('vinculo-card');
        $this->view('navegacao.redirecionar',['destino'=>['url'=>'/app/turmas/consultar','pagina'=>'/app/turmas/detalhes','referencia'=>str_repeat('a',48)]])
            ->assertSee('data-navegacao-redirecionar')->assertSee('data-navegacao-status')->assertDontSee('vinculo-card');
    }

    public function test_estrutura_comum_preserva_casca_e_nao_consulta_api_para_desenhar(): void
    {
        $this->withSession(['auth_token'=>'identidade-ui1-ficticia','auth_vinculos'=>true]);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        \App\Support\ContextoWeb::ativar(['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'contexto-ui1-ficticio',
            'perfil'=>'coordenador','permissoes'=>['turma.ver','prova.ver','gabarito.ver'],
            'espaco'=>['id'=>13,'tipo'=>'escola','nome'=>'Escola fictícia']]);
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        \Illuminate\Support\Facades\Http::fake();
        $r=$this->get('/app/turmas')->assertOk()->assertSee('estrutura-app')->assertSee('navegacao-esqueleto')
            ->assertSee('Menu principal')->assertSee('Meu espaço')->assertSee('aria-busy="true"',false)
            ->assertSee('estado-tela.js')->assertDontSee('vinculo-card');
        \Illuminate\Support\Facades\Http::assertNothingSent();
        $dom=new \DOMDocument();@$dom->loadHTML($r->getContent());
        $this->assertStringContainsString('area-logada',$dom->getElementsByTagName('body')->item(0)->getAttribute('class'));
    }
}
