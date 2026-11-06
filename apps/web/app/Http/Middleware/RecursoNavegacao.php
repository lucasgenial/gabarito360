<?php
namespace App\Http\Middleware;

use App\Support\{ContextoWeb,NavegacaoWeb,RotasNavegacao};
use Closure;
use Illuminate\Http\Request;

final class RecursoNavegacao
{
    public function handle(Request $r, Closure $next)
    {
        $rota = $r->route(); $meta = $rota?->getAction('ur4');
        if (!$meta) return $next($r);
        if (!session('auth_token')) return $r->expectsJson()
            ? response()->json(['message'=>'Entre novamente para continuar.','login_necessario'=>true],401) : redirect('/login');
        if ($r->isMethod('GET') && !$rota->getAction('ur4_consulta')) {
            if ($r->query->count()) return response()->view('navegacao.selecionar',['meta'=>$meta],409);
            if ($meta['binario'] || $meta['dados']) return response()->json(['message'=>'Abra este recurso pela tela correspondente.'],409);
            return response()->view('navegacao.estrutura',['meta'=>$meta]);
        }
        if ($r->query->count()) return response()->json(['message'=>'Envie a seleção no corpo da requisição.'],422);
        // O POST já atravessou CSRF e RevisaoAcesso antes de qualquer verbo lógico.
        $ref = $r->input('_recurso');
        $dados = NavegacaoWeb::obter($ref, $meta['nome']);
        if (!$dados) {
            if ($ref !== null || array_diff($meta['obrigatorios'], $meta['corpo'])) return response()->view('navegacao.selecionar',['meta'=>$meta],409);
            $dados = ['nome'=>$meta['nome'],'metodo'=>$meta['metodos'][0],'parametros'=>[],'consulta'=>[]];
        }
        $corpo = $r->all(); $params = $dados['parametros'];
        foreach ($meta['parametros'] as $p) {
            if (!array_key_exists($p,$params) && in_array($p,$meta['corpo'],true)) $params[$p] = $corpo[$p] ?? null;
            if (array_key_exists($p,$params)) {
                $v = $params[$p];
                if (!is_scalar($v) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/D',(string)$v)
                    || (isset($rota->wheres[$p]) && !preg_match('~^(?:'.$rota->wheres[$p].')$~D',(string)$v))
                    || (!isset($rota->wheres[$p]) && in_array($p,['id','uid','turma','componente','atribuicao','alunoId','provaId','turmaId'],true) && !preg_match('/^[1-9][0-9]{0,17}$/D',(string)$v))) abort(422,'Seleção inválida.');
                if (array_key_exists($p,$corpo) && (!is_scalar($corpo[$p]) || (string)$corpo[$p] !== (string)$v)) abort(422,'A seleção não corresponde a esta tela.');
                $rota->setParameter($p,$v);
            } elseif (!str_contains($meta['original'],'{'.$p.'?}')) abort(422,'Seleção incompleta.');
        }
        $retorno = NavegacaoWeb::obter($r->input('_retorno_tela'));
        if ($r->has('_retorno_tela') && !$retorno) return RevisaoAcesso::recusar($r);
        if ($retorno) $r->headers->set('referer', NavegacaoWeb::original($retorno));
        foreach (NavegacaoWeb::TRANSPORTE as $campo) unset($corpo[$campo]);
        foreach ($meta['corpo'] as $p) unset($corpo[$p]);
        $consulta = $dados['consulta'];
        $leitura = $rota->getAction('ur4_consulta');
        if ($leitura && $dados['metodo']==='GET') $consulta = array_replace($consulta,$corpo);
        $r->query->replace($consulta); $r->request->replace($corpo);
        $r->setMethod($dados['metodo']);
        $r->attributes->set('ur4_dados',['nome'=>$meta['nome'],'metodo'=>$dados['metodo'],'parametros'=>$params,'consulta'=>$consulta]);
        $r->attributes->set('ur4_referencia',$ref);
        $r->attributes->set('ur4_caminho_original',parse_url(NavegacaoWeb::original($r->attributes->get('ur4_dados')),PHP_URL_PATH));
        $acao=$rota->getAction();$acao['as']=$meta['nome'];$rota->setAction($acao);
        if ($leitura && !$r->headers->has('X-G360-Hidratar') && !$meta['binario'] && !$meta['dados']) {
            $destino=NavegacaoWeb::destino($meta['nome'],$params+$consulta,$dados['metodo']);
            return response()->view('navegacao.redirecionar',compact('destino'));
        }
        $mensagens = is_string($ref) ? NavegacaoWeb::mensagens($ref) : [];
        try { return $next($r); }
        finally { session()->forget($mensagens); }
    }
}
