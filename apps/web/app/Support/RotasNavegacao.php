<?php
namespace App\Support;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

/** Catálogo do BFF. Não contém caminhos nem controladores escolhidos pelo navegador. */
final class RotasNavegacao
{
    public static function configurar(Router $router): void
    {
        $originais = iterator_to_array($router->getRoutes());
        $usados = [];
        $reservados=[];
        $listas=[];
        foreach ($originais as $r) if (str_starts_with($r->uri(),'app/') && !str_contains($r->uri(),'{') && in_array('POST',$r->methods(),true)) $reservados[$r->uri()]=true;
        foreach ($originais as $r) if (str_starts_with($r->uri(),'app/') && !str_contains($r->uri(),'{') && in_array('GET',$r->methods(),true)) $listas[$r->uri()]=true;
        foreach ($originais as $rota) {
            $nome = $rota->getName();
            if (!$nome || !str_starts_with($rota->uri(), 'app/') || str_starts_with($nome, 'legado.')
                || str_starts_with($nome, 'vinculos.') || str_starts_with($rota->uri(), 'app/vinculos')
                || str_starts_with($nome, 'trocar-senha')) continue;
            $uri = $rota->uri();
            preg_match_all('/\{([^}?]+)\??\}/', $uri, $m);
            $parametros = $m[1];
            $metodos = array_values(array_diff($rota->methods(), ['HEAD']));
            $leitura = in_array('GET', $metodos, true);
            $fixo = preg_replace('~/\{[^}]+\}~', '', $uri);
            if ($parametros && preg_match('/\}$/', $uri)) {
                $sufixo = $leitura ? 'detalhes' : match ($metodos[0]) { 'PUT', 'PATCH' => 'atualizar', 'DELETE' => 'excluir', default => 'executar' };
                $fixo .= '/'.$sufixo;
            }
            // Cada operação é distinta, inclusive quando GET/PUT/DELETE tinham o mesmo template.
            if (isset($usados[$fixo]) && $parametros) $fixo .= '/'.str_replace('.', '-', $nome);
            if (!$leitura && !in_array('POST',$metodos,true) && isset($reservados[$fixo])) $fixo.='/atualizar';
            $usados[$fixo] = true;
            $consulta=$leitura ? $fixo.'/consultar' : $fixo;
            if ($leitura) { while(isset($reservados[$consulta])) $consulta.='-tela';$reservados[$consulta]=true; }
            $prefixo=explode('/{',$uri)[0];$modulo=explode('/',$uri)[1];
            $lista=isset($listas[$prefixo])?$prefixo:'app/'.$modulo;
            if (!isset($listas[$lista])) $lista=in_array($modulo,['resultados','cartoes','tentativas'],true)?'app/corrigir':'app/painel';
            $obrigatorios=array_values(array_filter($parametros,fn($p)=>!str_contains($uri,'{'.$p.'?}')));
            $meta = ['nome'=>$nome, 'original'=>$uri, 'pagina'=>$fixo, 'parametros'=>$parametros,'obrigatorios'=>$obrigatorios,'lista'=>'/'.$lista,
                'metodos'=>$metodos, 'leitura'=>$leitura, 'consulta'=>$consulta,
                'binario'=>in_array($nome, \App\Http\Middleware\RevisaoAcesso::BINARIOS, true),
                'dados'=>in_array($nome, \App\Http\Middleware\RevisaoAcesso::DADOS, true),
                'corpo'=>in_array($nome, ['correcao.envio','correcao.envio.repetir'], true) ? ['chave'] : []];
            $acao = $rota->getAction(); $acao['ur4'] = $meta;
            $rota->setAction($acao)->setUri($fixo);
            if ($leitura) {
                // O POST de consulta conserva a mesma ação e middleware, sem subrequest.
                $consulta = new Route(['POST'], $meta['consulta'], $acao);
                $consulta->defaults=$rota->defaults;
                $consulta->setRouter($router)->setContainer(app())->setUri($meta['consulta'])->where($rota->wheres);
                $consulta->setAction(array_merge($consulta->getAction(), ['as'=>'ur4.consulta.'.$nome,'ur4_consulta'=>true]));
                $router->getRoutes()->add($consulta);
            } else {
                $rota->methods = array_values(array_unique(array_merge($rota->methods(), ['POST'])));
            }
            if ($parametros) {
                $antiga = new Route(['GET','HEAD','POST','PUT','PATCH','DELETE'], $uri,
                    ['uses'=>\App\Http\Controllers\NavegacaoLegadaController::class.'@__invoke','controller'=>\App\Http\Controllers\NavegacaoLegadaController::class.'@__invoke', 'middleware'=>['web','autenticado'], 'ur4_legado'=>true]);
                $antiga->setRouter($router)->setContainer(app())->setUri($uri)->where($rota->wheres)->name('ur4.antiga.'.$nome);
                $router->getRoutes()->add($antiga);
            }
        }
        $todas=iterator_to_array($router->getRoutes());
        usort($todas,fn($a,$b)=>(int)(bool)$a->getAction('ur4_legado')<=>(int)(bool)$b->getAction('ur4_legado'));
        $colecao=new \Illuminate\Routing\RouteCollection();
        foreach($todas as $r)$colecao->add($r);
        $router->setRoutes($colecao);
        app()->instance('routes',$colecao);
    }

    public static function rota(string $nome): ?Route
    {
        $r = app('router')->getRoutes()->getByName($nome);
        return $r && isset($r->getAction()['ur4']) && !$r->getAction('ur4_consulta') ? $r : null;
    }
}
