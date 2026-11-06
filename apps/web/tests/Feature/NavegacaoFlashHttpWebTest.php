<?php
namespace Tests\Feature;

use App\Support\{ContextoWeb,NavegacaoWeb};
use Illuminate\Support\Facades\Http;
use Tests\Support\AcessoAtivoLegado;
use Tests\TestCase;

/** Exerce a cápsula real através do envelope StartSession, sem macro de restauração de flash. */
final class NavegacaoFlashHttpWebTest extends TestCase
{
    use AcessoAtivoLegado;

    public function test_flash_sobrevive_ponte_e_get_estrutural_ate_hidratacao_e_e_consumido_uma_vez(): void
    {
        $this->withSession(['auth_nome'=>'Pessoa fictícia']);
        $this->selecionarAcessoLegado('admin_rede',['usuario.ver','usuario.gerir']);
        Http::preventStrayRequests();
        $this->apiLegada([
            'api.test/api/v1/usuarios/7/toggle'=>Http::response(['data'=>['ativo'=>true]]),
            'api.test/api/v1/usuarios'=>Http::response(['data'=>['usuarios'=>[],'totais'=>[]]]),
        ]);
        $acao=NavegacaoWeb::destino('membros.toggle',['id'=>7]);$retorno=NavegacaoWeb::destino('membros.index');
        $r=$this->post($acao['url'],['_recurso'=>$acao['referencia'],'_retorno_tela'=>$retorno['referencia'],'_token'=>csrf_token(),'_revisao'=>ContextoWeb::revisao()])->assertOk();
        $dom=new \DOMDocument();@$dom->loadHTML($r->getContent());$xp=new \DOMXPath($dom);$ponte=$xp->query('//*[@data-navegacao-redirecionar]')->item(0);$this->assertNotNull($ponte);
        $destino=json_decode($ponte->getAttribute('data-recurso'),true,512,JSON_THROW_ON_ERROR);
        $this->assertSame('membros.index',NavegacaoWeb::obter($destino['referencia'])['nome']);
        $this->assertNull(parse_url($destino['pagina'],PHP_URL_QUERY));$this->assertNull(session('sucesso'));
        // O GET é apenas estrutura: não consome a caixa da referência nem repete a alteração.
        $this->get($destino['pagina'])->assertOk()->assertSee('data-navegacao-estrutura',false)->assertDontSee('Membro ativado.');
        $campos=['_recurso'=>$destino['referencia'],'_token'=>csrf_token(),'_revisao'=>ContextoWeb::revisao()];
        $this->post($destino['url'],$campos,['X-G360-Hidratar'=>'1'])->assertOk()->assertSee('Membro ativado.');
        $this->assertNull(session('sucesso'));
        $this->post($destino['url'],$campos,['X-G360-Hidratar'=>'1'])->assertOk()->assertDontSee('Membro ativado.');
        $escritas=Http::recorded(fn($q)=>$q->method()==='POST'&&str_ends_with($q->url(),'/usuarios/7/toggle'));
        $this->assertCount(1,$escritas);
    }
}
