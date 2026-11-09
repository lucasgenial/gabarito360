<?php
namespace App\Services;

use Illuminate\Http\{JsonResponse,Request};
use Illuminate\Validation\ValidationException;

final class BootstrapEditorPainel
{
    public static function solicitado(Request $r):bool
    {
        $v=$r->headers->all('X-G360-Painel-Editor');if(!$v)return false;
        if($v!==['3'])throw ValidationException::withMessages(['editor'=>'Versão de editor inválida.']);return true;
    }
    public static function etag(array $p):string
    { return '"g360-editor-'.hash('sha256',json_encode([$p['editor']['contexto'],$p['versao'],$p['editor']['catalogo_chave'],3,\App\Support\CacheComposicaoPainel::PROJETOR,config('paineis.versao')],JSON_THROW_ON_ERROR)).'"'; }
    public function anexar(JsonResponse $resposta,Request $r,string $painel,?int $turma=null):JsonResponse
    {
        if(!self::solicitado($r))return $resposta;
        // O editor requer contexto explícito; a extensão não derruba os dados
        // já autorizados pelo contrato legado quando esse regime está desligado.
        if(!\App\Support\Espacos::ativo()||!\App\Support\ContextoVinculo::presente($r->user()))return $resposta->header('Cache-Control','private, no-store');
        $p=app(PreferenciasPainel::class)->mostrar($r->user(),$painel,$turma,true);
        $d=$resposta->getData(true);$d['meta']['editor_painel']=$p;$d['meta']['editor_etag']=self::etag($p);
        return $resposta->setData($d)->header('Cache-Control','private, no-store');
    }
}
