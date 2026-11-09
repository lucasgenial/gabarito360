<?php
namespace App\Support;

use Illuminate\Support\Facades\Cache;

/** Cache técnico e derivado. Versão e títulos sempre vêm da linha atual do banco. */
final class CacheComposicaoPainel
{
    public const PROJETOR=1;
    public static function chave(string $contexto,int $versao,array $defs):string
    { return 'g360:editor:'.hash('sha256',json_encode([$contexto,$versao,ComposicaoPainel::chave($defs),3,self::PROJETOR,config('paineis.versao')],JSON_THROW_ON_ERROR)); }

    public static function projetar(?array $stored,string $painel,array $defs,array $legado,string $contexto,int $versao):array
    {
        // Nem title nem originalTitle livres entram no cache, mesmo no servidor.
        $limpo=$stored;
        if($limpo!==null){foreach($limpo['sections'] as &$n){$n['title']='Seção';$n['originalTitle']='Seção';}unset($n);foreach($limpo['widgets'] as &$n)$n['title']='Widget';unset($n);}
        $calcular=static function()use($limpo,$painel,$defs,$legado){
            $s=ComposicaoPainel::projetar($limpo,$painel,$defs,$legado);
            foreach($s['sections'] as &$n)unset($n['title'],$n['originalTitle']);unset($n);
            foreach($s['widgets'] as &$n)unset($n['title']);unset($n);return $s;
        };
        // Cache no próprio DB acrescentaria SQL à única leitura indexada necessária.
        $driver=config('cache.default');
        if(in_array($driver,['array','file','redis','memcached'],true)){
            try{$s=Cache::remember(self::chave($contexto,$versao,$defs),300,$calcular);}catch(\Throwable $e){if($e instanceof \Illuminate\Validation\ValidationException)throw $e;$s=$calcular();}
        }else{$s=$calcular();}
        $hidratar=static function($s)use($stored,$defs):array{
            if(!is_array($s)||!is_array($s['sections']??null)||!is_array($s['widgets']??null))throw new \UnexpectedValueException('Cache técnico inválido.');
            $antigas=array_column($stored['sections']??[],null,'id');$originais=array_column($defs['sections'],null,'id');$widgets=array_column($defs['widgets'],null,'id');
            foreach($s['sections'] as &$n){if(!is_array($n)||!is_string($n['id']??null)||!isset($antigas[$n['id']])&&!isset($originais[$n['id']]))throw new \UnexpectedValueException('Seção de cache inválida.');$n['title']=$antigas[$n['id']]['title']??$originais[$n['id']]['title'];$n['originalTitle']=$antigas[$n['id']]['originalTitle']??$originais[$n['id']]['title'];}unset($n);
            foreach($s['widgets'] as $id=>&$n){if(!is_array($n)||!isset($widgets[$id]))throw new \UnexpectedValueException('Widget de cache inválido.');$n['title']=$stored['widgets'][$id]['title']??$widgets[$id]['title'];}unset($n);
            return ComposicaoPainel::validar($s,$defs);
        };
        try{return $hidratar($s);}catch(\Throwable){
            try{Cache::forget(self::chave($contexto,$versao,$defs));}catch(\Throwable){}
            // A fonte corrente já foi lida e autorizada: não repetir requests nem servir dado antigo.
            return $hidratar($calcular());
        }
    }
}
