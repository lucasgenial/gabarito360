<?php
namespace Tests\Feature;

use App\Support\NavegacaoWeb;
use Illuminate\Support\Facades\Http;
use Tests\Support\AcessoAtivoLegado;
use Tests\TestCase;

class NavegacaoIntegracaoWebTest extends TestCase
{
    use AcessoAtivoLegado;
    protected function setUp(): void
    {
        parent::setUp();Http::preventStrayRequests();
        $this->selecionarAcessoLegado('coordenador',['turma.ver','turma.gerir']);
    }

    public function test_estrutura_get_nao_escolhe_recurso_nem_consulta_api(): void
    {
        $this->get('/app/turmas/detalhes')->assertOk()->assertSee('data-navegacao-estrutura',false)->assertDontSee('Desempenho médio');
        Http::assertNothingSent();
    }

    public function test_textos_de_negocio_nao_sao_convertidos_em_destinos_de_navegacao(): void
    {
        $texto='/app/turmas/14';
        $entrada=['nome'=>$texto,'titulo'=>$texto,'observacao'=>'<b>Texto literal</b>',
            'filhos'=>[['nome'=>$texto]],'url'=>$texto,'foto_url'=>$texto,'url_arquivo'=>$texto,'fotoUrl'=>$texto];
        $r=request();$r->setLaravelSession(session()->driver());
        $resposta=app(\App\Http\Middleware\RespostaNavegacao::class)->converter($r,response()->json($entrada));
        $dados=json_decode($resposta->getContent(),true,512,JSON_THROW_ON_ERROR);
        foreach(['nome','titulo','observacao','filhos'] as $campo)$this->assertSame($entrada[$campo],$dados[$campo]);
        foreach(['url','foto_url','url_arquivo','fotoUrl'] as $campo){
            $this->assertSame('/app/turmas/detalhes',$dados[$campo]['pagina']);
            $alvo=NavegacaoWeb::obter($dados[$campo]['referencia']);
            $this->assertSame('turmas.show',$alvo['nome']);
            $this->assertSame('14',(string)$alvo['parametros']['id']);
        }
        Http::assertNothingSent();
    }

    public function test_hidratacao_chega_ao_controller_e_preserva_falha_temporaria(): void
    {
        $this->apiLegada(['*'=>Http::response(['message'=>'privado'],503)]);
        $d=NavegacaoWeb::destino('turmas.show',[14]);
        $this->withHeader('X-G360-Hidratar','1')->post($d['url'],['_recurso'=>$d['referencia']])
            ->assertStatus(503)->assertSee('Não foi possível carregar esta turma agora')->assertDontSee('privado');
        Http::assertSent(fn($r)=>$r->method()==='GET' && $r->url()==='http://api.test/api/v1/turmas/14');
    }

    public function test_html_real_emite_destinos_sem_id_e_formularios_com_referencias(): void
    {
        $this->apiLegada([
            'api.test/api/v1/turmas/14'=>Http::response(['data'=>['id'=>14,'nome'=>'Turma A','serie'=>'3ª série','turno'=>'tarde','ano_letivo'=>2026,'ativo'=>true,'escola_nome'=>'Escola fictícia','total_alunos'=>0,'media_turma'=>null,'alunos'=>[],'provas'=>[]]]),
            '*'=>Http::response(['data'=>[]]),
        ]);
        $d=NavegacaoWeb::destino('turmas.show',['id'=>14]);
        $r=$this->withHeader('X-G360-Hidratar','1')->post($d['url'],['_recurso'=>$d['referencia']]);
        $r->assertOk()->assertSee('Turma A');
        $linhas=array_values(array_filter(explode("\n",$r->getContent()),fn($l)=>str_contains($l,'/app/turmas/14')));
        $this->assertSame([],$linhas,'A resposta não pode emitir URL de recurso com ID.');
        $r->assertSee('data-recurso=',false)
            ->assertSee('name="_retorno_tela"',false)->assertSee('g360-pagina-renderizada');
        Http::assertSent(fn($r)=>$r->url()==='http://api.test/api/v1/turmas/14');
    }
}
