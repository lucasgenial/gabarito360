<?php
namespace Tests\Support;

use App\Support\{ContextoWeb,NavegacaoWeb};
use Illuminate\Testing\TestResponse;

/** Transporte UR4 explícito: as fixtures de negócio não exercitam URLs legadas. */
trait NavegacaoHttp
{
    protected function consultarTela(string $uri, array $dados = []): TestResponse
    {
        return $this->recursoHttp('GET',$uri,$dados,false);
    }

    protected function consultarJson(string $uri, array $dados = []): TestResponse
    {
        return $this->recursoHttp('GET',$uri,$dados,true);
    }

    protected function alterarRecurso(string $metodo, string $uri, array $dados = []): TestResponse
    {
        return $this->recursoHttp($metodo,$uri,$dados,false);
    }

    protected function alterarRecursoJson(string $metodo, string $uri, array $dados = []): TestResponse
    {
        return $this->recursoHttp($metodo,$uri,$dados,true);
    }

    protected function verificarRetornoTela(TestResponse $resposta, ?string $uri = null): TestResponse
    {
        $this->registrarAssercoesNavegacao();
        return $resposta->assertRetornoTela($uri);
    }

    protected function extrairFormularioRecurso(string $html,string $nome,array $parametros=[]): array
    {
        $dom=new \DOMDocument();@$dom->loadHTML($html);$xp=new \DOMXPath($dom);
        foreach ($xp->query('//form[@data-recurso]') as $form) {
            $desc=json_decode($form->getAttribute('data-recurso'),true,512,JSON_THROW_ON_ERROR);$d=NavegacaoWeb::obter($desc['referencia']);
            if (($d['nome']??null)!==$nome || array_intersect_key($d['parametros'],$parametros)!=$parametros)continue;
            $campos=[];foreach($xp->query('.//input[@type="hidden"]',$form) as $i)$campos[$i->getAttribute('name')]=$i->getAttribute('value');
            foreach (['_token','_revisao','_recurso'] as $campo)$this->assertNotEmpty($campos[$campo]);
            return ['url'=>$form->getAttribute('action'),'campos'=>$campos,'destino'=>$desc];
        }
        $this->fail('Formulário SSR de operação não encontrado: '.$nome.' '.json_encode($parametros));
    }

    private function registrarAssercoesNavegacao(): void
    {
        if (!TestResponse::hasMacro('assertRetornoTela')) TestResponse::macro('assertRetornoTela',function (?string $uriEsperada=null) {
            $this->assertOk();$dom=new \DOMDocument();@$dom->loadHTML($this->getContent());$xp=new \DOMXPath($dom);
            $ponte=$xp->query('//*[@data-navegacao-redirecionar]')->item(0);
            \PHPUnit\Framework\Assert::assertNotNull($ponte,'O retorno precisa ser uma ponte de navegação explícita.');
            $destino=json_decode($ponte->getAttribute('data-recurso'),true,512,JSON_THROW_ON_ERROR);
            $d=NavegacaoWeb::obter($destino['referencia']);\PHPUnit\Framework\Assert::assertNotNull($d,'Referência de retorno resolvível na mesma sessão.');
            \PHPUnit\Framework\Assert::assertSame('GET',$d['metodo']);
            \PHPUnit\Framework\Assert::assertStringNotContainsString('?',$destino['pagina']);
            if ($uriEsperada!==null) {
                $esperado=NavegacaoWeb::deUrl($uriEsperada,'GET',true);\PHPUnit\Framework\Assert::assertNotNull($esperado,'Destino esperado deve ser uma operação registrada.');
                $e=NavegacaoWeb::obter($esperado['referencia']);
                foreach (['nome','metodo','parametros','consulta'] as $campo) \PHPUnit\Framework\Assert::assertEquals($e[$campo],$d[$campo],$campo.' do retorno preservado.');
            }
            // Reabre o Store como TestResponse faz: não deixe start() posterior sobrescrever a cápsula restaurada.
            $store=app('session.store');if(!$store->isStarted())$store->start();
            // Lê a cápsula emitida pelo aplicativo. Não inventa flash nem segue uma mutação.
            session()->forget(['errors','_old_input','erro','sucesso','aviso','conflito_dc1','status']);
            NavegacaoWeb::mensagens($destino['referencia']);
            return $this;
        });
        if (!TestResponse::hasMacro('assertDestinoRecurso')) TestResponse::macro('assertDestinoRecurso',function (string $nome,array $parametros=[],array $consulta=[]) {
            $dom=new \DOMDocument();@$dom->loadHTML($this->getContent());$xp=new \DOMXPath($dom);$encontrado=false;
            foreach ($xp->query('//*[@data-recurso or @data-historico-recurso]') as $el) {
                $desc=json_decode($el->getAttribute($el->hasAttribute('data-recurso')?'data-recurso':'data-historico-recurso'),true);
                if(!is_array($desc)||!isset($desc['referencia']))continue;
                $dados=NavegacaoWeb::obter($desc['referencia']);if(($dados['nome']??null)!==$nome)continue;
                if(array_intersect_key($dados['parametros'], $parametros)!=$parametros || array_intersect_key($dados['consulta'],$consulta)!=$consulta)continue;
                \PHPUnit\Framework\Assert::assertStringNotContainsString('?',$desc['url']);$encontrado=true;break;
            }
            \PHPUnit\Framework\Assert::assertTrue($encontrado,'Destino de recurso preservado: '.$nome.' '.json_encode([$parametros,$consulta]));return $this;
        });
    }

    private function recursoHttp(string $metodo, string $uri, array $dados, bool $json): TestResponse
    {
        $this->registrarAssercoesNavegacao();
        // Representa uma nova emissão SSR da fixture, não o snapshot da requisição anterior.
        foreach (['g360_snapshot','g360_identidade','g360_revisao','vinculo_contexto','vinculo_handle'] as $campo) request()->attributes->remove($campo);
        ContextoWeb::capturar(request());
        $destino=NavegacaoWeb::deUrl($uri,strtoupper($metodo),true);
        if (!$destino) throw new \LogicException('Operação Web não registrada na fixture: '.strtoupper($metodo).' '.$uri);
        $corpo=$dados+['_recurso'=>$destino['referencia'],'_revisao'=>ContextoWeb::revisao(),'_token'=>csrf_token()];
        // Somente o catálogo define o método lógico; o transporte HTTP é sempre POST.
        unset($corpo['_method']);
        $origem=$this->defaultHeaders['referer']??$this->defaultHeaders['Referer']??null;
        if (!isset($corpo['_retorno_tela']) && is_string($origem) && ($voltar=NavegacaoWeb::deUrl($origem,'GET',true))) $corpo['_retorno_tela']=$voltar['referencia'];
        return $json
            ? $this->postJson($destino['url'],$corpo,['X-G360-Hidratar'=>'1'])
            : $this->post($destino['url'],$corpo,['X-G360-Hidratar'=>'1']);
    }
}
