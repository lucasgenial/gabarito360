<?php
namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/** Referência de navegação não autorizadora, sempre transportada no corpo. */
final class NavegacaoWeb
{
    public const TRANSPORTE = ['_recurso','_retorno_tela','_hidratar','_token','_revisao','_method'];

    private static function escopo(): string
    {
        return hash('sha256', session()->getId().'|'.ContextoWeb::identidade().'|'.ContextoWeb::revisao());
    }

    public static function destino(string $nome, array $parametros = [], ?string $metodo = null): array
    {
        $rota = RotasNavegacao::rota($nome);
        if (!$rota) throw new \InvalidArgumentException('Operação de navegação desconhecida.');
        $meta = $rota->getAction('ur4');
        $posicao=0;
        foreach ($parametros as $k=>$v) if (is_int($k)) {
            $p=$meta['parametros'][$posicao++]??null;
            if (!$p || array_key_exists($p,$parametros)) throw new \InvalidArgumentException('Parâmetros de navegação ambíguos.');
            unset($parametros[$k]);$parametros[$p]=$v;
        }
        $metodo ??= $meta['binario'] && in_array('POST', $meta['metodos'], true) ? 'POST' : $meta['metodos'][0];
        if (!in_array($metodo, $meta['metodos'], true)) throw new \InvalidArgumentException('Método não previsto.');
        $alvos = []; $consulta = $parametros;
        foreach ($meta['parametros'] as $p) {
            if (array_key_exists($p, $consulta)) { $alvos[$p] = $consulta[$p]; unset($consulta[$p]); }
            elseif (!in_array($p, $meta['corpo'], true) && !str_contains($meta['original'], '{'.$p.'?}')) throw new \InvalidArgumentException('Alvo de navegação incompleto.');
        }
        $dados = ['nome'=>$nome,'metodo'=>$metodo,'parametros'=>$alvos,'consulta'=>$consulta];
        $json = json_encode($dados, JSON_THROW_ON_ERROR);
        $ref = substr(hash_hmac('sha256', $json, self::escopo()), 0, 48);
        Cache::put('ur4:'.self::escopo().':'.$ref, Crypt::encryptString($json), now()->addMinutes((int) config('session.lifetime', 120)));
        return ['url'=>'/'.$meta['consulta'], 'pagina'=>'/'.$meta['pagina'], 'referencia'=>$ref, 'metodo'=>$metodo];
    }

    public static function obter(mixed $ref, ?string $nome = null): ?array
    {
        if (!is_string($ref) || !preg_match('/^[a-f0-9]{48}$/D', $ref)) return null;
        $raw = Cache::get('ur4:'.self::escopo().':'.$ref);
        if (!is_string($raw)) return null;
        try { $d = json_decode(Crypt::decryptString($raw), true, 32, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { return null; }
        if (!is_array($d) || ($nome !== null && ($d['nome'] ?? null) !== $nome) || !RotasNavegacao::rota($d['nome'] ?? '')) return null;
        return $d;
    }

    /** Apenas URLs produzidas pelo próprio BFF; nunca aceita um host/API arbitrário. */
    public static function deUrl(string $url, ?string $metodo = null, bool $todos = false): ?array
    {
        if ($url === '' || str_starts_with($url, '#')) return null;
        $u = parse_url(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (!$u || isset($u['user']) || isset($u['pass'])) return null;
        if (isset($u['host']) && strtolower($u['host']) !== strtolower(request()->getHost())) return null;
        if (isset($u['scheme']) && !in_array(strtolower($u['scheme']), ['http','https'], true)) return null;
        $path = $u['path'] ?? ''; if (!str_starts_with($path, '/app/')) return null;
        parse_str($u['query'] ?? '', $query);
        $catalogo=self::catalogoDeUrls();
        if (isset($catalogo['fixos'][$path])) return null;
        foreach ($catalogo['originais'] as $entrada) {
            $meta=$entrada['meta'];
            if ($metodo && !in_array($metodo, $meta['metodos'], true)) continue;
            if (!preg_match($entrada['regex'], $path, $m)) continue;
            $params = $query;
            foreach ($meta['parametros'] as $p) if (isset($m[$p]) && $m[$p] !== '') $params[$p] = rawurldecode($m[$p]);
            // Módulos sem alvo ou query continuam como links GET simples.
            if (!$todos && !$meta['parametros'] && !$query && !$meta['binario'] && !$meta['dados']) return null;
            return self::destino($meta['nome'], $params, $metodo);
        }
        return null;
    }

    /** Compilar o catálogo uma vez por requisição, sem guardar alvos ou autoridade. */
    private static function catalogoDeUrls(): array
    {
        $request=request();
        if ($request->attributes->has('ur4_catalogo_urls')) return $request->attributes->get('ur4_catalogo_urls');
        $catalogo=['fixos'=>[],'originais'=>[]];
        foreach (app('router')->getRoutes() as $rota) {
            if (!($meta=$rota->getAction('ur4')) || $rota->getAction('ur4_consulta')) continue;
            if ($meta['consulta']!==$meta['original']) $catalogo['fixos']['/'.$meta['consulta']]=true;
            if ($meta['parametros']) $catalogo['fixos']['/'.$meta['pagina']]=true;
            $teste=new \Illuminate\Routing\Route($rota->methods(),$meta['original'],$rota->getAction());
            $teste->setUri($meta['original'])->where($rota->wheres);
            $catalogo['originais'][]=['meta'=>$meta,'regex'=>$teste->toSymfonyRoute()->compile()->getRegex()];
        }
        $request->attributes->set('ur4_catalogo_urls',$catalogo);
        return $catalogo;
    }

    public static function original(array $dados): string
    {
        return route($dados['nome'], $dados['parametros'] + $dados['consulta']);
    }

    /** Cada retorno tem sua própria caixa de mensagens, inclusive entre abas do mesmo alvo. */
    public static function individualizar(array $destino): array
    {
        $dados=self::obter($destino['referencia']);
        if (!$dados) throw new \RuntimeException('Seleção de retorno indisponível.');
        $dados['entrada']=bin2hex(random_bytes(16));
        $json=json_encode($dados,JSON_THROW_ON_ERROR);
        $ref=substr(hash_hmac('sha256',$json,self::escopo()),0,48);
        Cache::put('ur4:'.self::escopo().':'.$ref,Crypt::encryptString($json),now()->addMinutes((int)config('session.lifetime',120)));
        $destino['referencia']=$ref;
        return $destino;
    }

    public static function atual(\Illuminate\Http\Request $request): ?array
    {
        if (!($d=$request->attributes->get('ur4_dados'))) return null;
        $destino=self::destino($d['nome'],$d['parametros']+$d['consulta'],$d['metodo']);
        $ref=$request->attributes->get('ur4_referencia');
        $anterior=self::obter($ref,$d['nome']);
        if ($anterior && $anterior['parametros']===$d['parametros'] && $anterior['consulta']===$d['consulta']) $destino['referencia']=$ref;
        return $destino;
    }

    public static function guardarMensagens(string $ref): void
    {
        $nomes = array_intersect(session()->get('_flash.new', []), ['errors','_old_input','erro','sucesso','aviso','info','conflito_dc1','status','erro_imagem','sucesso_imagem','cartao_envio','confirmacao_area','edicao_prova_erro','foco_area']);
        $valores = session()->only($nomes);
        if ($valores) Cache::put('ur4-mensagem:'.self::escopo().':'.$ref, Crypt::encrypt($valores), now()->addMinutes(10));
        session()->forget($nomes);
    }

    public static function mensagens(string $ref): array
    {
        $valor = Cache::pull('ur4-mensagem:'.self::escopo().':'.$ref);
        if (!is_string($valor)) return [];
        try { $dados = Crypt::decrypt($valor); } catch (\Throwable) { return []; }
        if (!is_array($dados)) return [];
        foreach ($dados as $k=>$v) session()->now($k,$v);
        \Illuminate\Support\Facades\View::share('errors', session('errors',new \Illuminate\Support\ViewErrorBag()));
        return array_keys($dados);
    }
}
