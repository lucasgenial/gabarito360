<?php
namespace Tests\Feature;

use App\Http\Middleware\RespostaNavegacao;
use App\Support\{ContextoWeb,NavegacaoWeb};
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Emissão transversal do catálogo, sem executar controllers/API nem certificar ACL. */
final class NavegacaoEmissaoCatalogoWebTest extends TestCase
{
    private const UUID='a8d47152-413e-4f8d-9d4b-8e19f001a111';

    public static function apresentacoes(): array { return ['documento'=>[false],'fragmento'=>[true]]; }

    #[DataProvider('apresentacoes')]
    public function test_toda_operacao_parametrizada_emite_destino_fixo_e_snapshot_exato_no_html(bool $fragmento): void
    {
        $this->withSession(['auth_token'=>'identidade-catalogo-ficticia','auth_vinculos'=>true]);
        ContextoWeb::ativar(['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'contexto-catalogo-ficticio',
            'perfil'=>'professor','permissoes'=>[],'espaco'=>['id'=>13,'tipo'=>'escola']]);
        Http::preventStrayRequests();
        $html=$fragmento?'<section>':'<!doctype html><html><head><title>Catálogo fictício</title></head><body><main>';
        $casos=[];$operacoes=[];$modulos=[];$indice=0;
        foreach(app('router')->getRoutes() as $rota){
            $m=$rota->getAction('ur4');
            if(!$m||$rota->getAction('ur4_consulta')||!$m['parametros'])continue;
            $operacoes[]=$m['nome'];$modulos[explode('/',$m['original'])[1]]=true;
            $params=[];
            foreach($m['parametros'] as $n=>$param){
                $where=$rota->wheres[$param]??null;
                $valor=$where&&str_contains($where,'{8}-')?self::UUID:
                    ($where&&str_contains($where,'|')?explode('|',$where)[0]:(string)(841273+$n));
                if($where)$this->assertMatchesRegularExpression('~^(?:'.$where.')$~D',$valor,$m['nome'].' '.$param);
                $params[$param]=$valor;
            }
            foreach($m['metodos'] as $metodo){
                $id='caso-'.++$indice;
                // Gerador de URL do aplicativo emite o template original só no servidor.
                $original=route($m['nome'],$params,false);$url=htmlspecialchars($original,ENT_QUOTES,'UTF-8');
                if($metodo==='GET'){
                    $html.='<a id="'.$id.'" href="'.$url.'">Consultar</a>';
                    $casos[$id]=[$m,$params,$metodo,'href'];
                    if($m['binario'] && preg_match('/(?:foto|logo|imagem)/',$m['nome'])){
                        $img=$id.'-imagem';$html.='<img id="'.$img.'" src="'.$url.'" alt="Imagem fictícia">';
                        $casos[$img]=[$m,$params,$metodo,'src'];
                    }
                }else{
                    $html.='<form id="'.$id.'" action="'.$url.'" method="POST">';
                    if($metodo!=='POST')$html.='<input type="hidden" name="_method" value="'.$metodo.'">';
                    $html.='<button>Enviar</button></form>';
                    $casos[$id]=[$m,$params,$metodo,'action'];
                }
            }
        }
        $html.=$fragmento?'</section>':'</main></body></html>';
        $r=request();$r->setLaravelSession(session()->driver());
        $res=app(RespostaNavegacao::class)->converter($r,response($html,200,['Content-Type'=>'text/html; charset=UTF-8']));
        $dom=new \DOMDocument();@$dom->loadHTML($res->getContent());$xp=new \DOMXPath($dom);
        foreach($casos as $id=>[$m,$params,$metodo,$attr]){
            $el=$xp->query('//*[@id="'.$id.'"]')->item(0);$this->assertNotNull($el,$id);
            $raw=$el->getAttribute($attr==='src'?'data-imagem-contextual':'data-recurso');
            $this->assertNotEmpty($raw,$m['nome'].' '.$metodo.' sem descritor');
            $d=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
            $this->assertSame('/'.$m['consulta'],$d['url'],$m['nome']);
            $this->assertSame('/'.$m['pagina'],$d['pagina'],$m['nome']);
            $snapshot=NavegacaoWeb::obter($d['referencia']);$this->assertNotNull($snapshot);
            $this->assertSame($m['nome'],$snapshot['nome']);$this->assertSame($metodo,$snapshot['metodo'],$m['nome'].' '.$attr);
            $this->assertSame($params,$snapshot['parametros']);$this->assertSame([],$snapshot['consulta']);
            foreach(['url','pagina'] as $k){
                $this->assertNull(parse_url($d[$k],PHP_URL_QUERY));$this->assertNull(parse_url($d[$k],PHP_URL_FRAGMENT));
                $this->assertStringNotContainsString(self::UUID,$d[$k]);
                foreach($params as $v)if(ctype_digit($v))$this->assertStringNotContainsString($v,$d[$k]);
            }
            if($attr==='src')$this->assertStringStartsWith('data:image/',$el->getAttribute('src'));
            else $this->assertSame($attr==='href'?$d['pagina']:$d['url'],$el->getAttribute($attr));
            if($attr==='action'){
                $this->assertSame('POST',$el->getAttribute('method'));
                foreach(['_recurso','_token','_revisao'] as $campo)$this->assertSame(1,$xp->query('.//input[@name="'.$campo.'"]',$el)->length);
            }
        }
        $this->assertGreaterThanOrEqual(119,count($operacoes));
        $this->assertGreaterThanOrEqual(15,count($modulos));
        Http::assertNothingSent();
    }
}
