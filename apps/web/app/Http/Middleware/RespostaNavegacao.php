<?php
namespace App\Http\Middleware;

use App\Support\NavegacaoWeb;
use Closure;
use DOMDocument;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Emissão SSR/JSON: referências só em dados e corpos, nunca em URL. */
final class RespostaNavegacao
{
    public function handle(Request $r, Closure $next): Response
    {
        return $this->converter($r,$next($r));
    }

    public function converter(Request $r, Response $resposta): Response
    {
        if (!$r->hasSession() || !session('auth_token')) return $resposta;
        if ($resposta->headers->has('X-G360-Navegacao')) return $resposta;
        $resposta->headers->set('X-G360-Navegacao','1');
        if ($resposta->isRedirection() && ($r->routeIs('vinculos.*','login.store','trocar-senha.store') || $r->route()?->getAction('ur4_legado'))) return $resposta;
        if ($resposta->isRedirection() && ($location=$resposta->headers->get('Location'))
            && ($destino=NavegacaoWeb::deUrl($location,'GET',true))) {
            $destino=NavegacaoWeb::individualizar($destino);
            NavegacaoWeb::guardarMensagens($destino['referencia']);
            return response()->view('navegacao.redirecionar',compact('destino'))->withHeaders(['Cache-Control'=>'private, no-store','Referrer-Policy'=>'no-referrer','X-G360-Navegacao'=>'1']);
        }
        if (!$resposta instanceof \Illuminate\Http\Response && !$resposta instanceof \Illuminate\Http\JsonResponse) return $resposta;
        $tipo = strtolower($resposta->headers->get('Content-Type',''));
        if (str_contains($tipo,'application/json')) {
            $dados=json_decode($resposta->getContent(),true);
            if (is_array($dados)) $resposta->setContent(json_encode($this->dados($dados),JSON_THROW_ON_ERROR));
        } elseif (str_contains($tipo,'text/html')) {
            $conteudo=$resposta->getContent();
            $resposta->setContent($this->html($conteudo,$r,!preg_match('/<html[\s>]/i',$conteudo)));
        }
        if ($destino=NavegacaoWeb::atual($r)) {
            $resposta->headers->set('X-G360-Recurso',$destino['referencia']);
        }
        $resposta->headers->remove('Content-Length');
        return $resposta;
    }

    private function dados(array $dados): array
    {
        if (isset($dados['url'],$dados['pagina'],$dados['referencia'],$dados['metodo'])) return $dados;
        foreach ($dados as $campo => &$v) {
            if (is_array($v)) $v=$this->dados($v);
            elseif ($campo === 'html' && is_string($v)) $v=$this->html($v,request(),true);
            elseif (is_string($v) && ($d=NavegacaoWeb::deUrl($v))) $v=$d;
        }
        return $dados;
    }

    private function campo(DOMDocument $dom, \DOMElement $form, string $nome, string $valor): void
    {
        foreach ($form->getElementsByTagName('input') as $i) if ($i->getAttribute('name')===$nome) return;
        $i=$dom->createElement('input');$i->setAttribute('type','hidden');$i->setAttribute('name',$nome);$i->setAttribute('value',$valor);$form->appendChild($i);
    }

    private function html(string $html, Request $r, bool $fragmento = false): string
    {
        $dom=new DOMDocument('1.0','UTF-8');$antes=libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($antes);
        $atual=null;
        if ($atual=NavegacaoWeb::atual($r)) {
            $m=$dom->createElement('meta');$m->setAttribute('name','g360-pagina-renderizada');$m->setAttribute('content',json_encode($atual,JSON_THROW_ON_ERROR));$dom->getElementsByTagName('head')->item(0)?->appendChild($m);
        }
        foreach ($dom->getElementsByTagName('*') as $el) {
            if (!$el instanceof \DOMElement) continue;
            if ($el->tagName==='script' && $el->getAttribute('type')==='application/json') {
                $dados=json_decode($el->textContent,true);
                if (is_array($dados)) $el->textContent=json_encode($this->dados($dados),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
                continue;
            }
            $attrs=[];foreach ($el->attributes as $attr) $attrs[$attr->name]=$attr->value;
            foreach ($attrs as $nome=>$valor) {
                if (!in_array($nome,['href','action','formaction','src','data-imagem-contextual','data-acao','data-municipios'],true) && !str_ends_with($nome,'-url')) continue;
                $metodo=null;
                if ($nome==='href') $metodo='GET';
                if ($el->tagName==='form' && $nome==='action') {
                    $metodo=strtoupper($el->getAttribute('method')?:'GET');
                    foreach ($el->getElementsByTagName('input') as $i) if ($i->getAttribute('name')==='_method') $metodo=strtoupper($i->getAttribute('value'));
                }
                $destino=NavegacaoWeb::deUrl($valor,$metodo, $el->tagName==='form' || $nome==='formaction');
                if (!$destino) continue;
                $json=json_encode($destino,JSON_THROW_ON_ERROR);
                if ($el->tagName==='img' && $nome==='src') {
                    $el->setAttribute('src','data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
                    $el->setAttribute('data-imagem-contextual',$json);
                } elseif (str_starts_with($nome,'data-')) {
                    $el->setAttribute($nome,$json);$el->setAttribute('data-recurso',$json);
                } else {
                    $el->setAttribute($nome,$nome==='href'?$destino['pagina']:$destino['url']);
                    $el->setAttribute('data-recurso',$json);
                }
                if ($el->tagName==='form' && $nome==='action') {
                    $el->setAttribute('method','POST');
                    $this->campo($dom,$el,'_recurso',$destino['referencia']);
                    $this->campo($dom,$el,'_token',csrf_token());
                    $this->campo($dom,$el,'_revisao',\App\Support\ContextoWeb::revisao()??'');
                    if ($destino['metodo']==='GET') $el->setAttribute('data-navegacao-consulta','');
                }
            }
            if ($atual && $el->tagName==='form' && strtoupper($el->getAttribute('method'))!=='GET') $this->campo($dom,$el,'_retorno_tela',$atual['referencia']);
        }
        if ($fragmento) {
            $saida='';foreach ($dom->getElementsByTagName('body')->item(0)?->childNodes??[] as $child) $saida.=$dom->saveHTML($child);
            return $saida;
        }
        return '<!doctype html>'."\n".$dom->saveHTML($dom->documentElement);
    }
}
