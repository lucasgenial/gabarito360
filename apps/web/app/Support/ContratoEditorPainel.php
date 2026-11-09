<?php
namespace App\Support;

/** Fronteira de transporte da Web. Não consulta banco nem concede widgets. */
final class ContratoEditorPainel
{
    private static function texto($v,int $max):bool {return is_string($v)&&trim($v)!==''&&mb_strlen($v)<=$max;}
    public static function valido(mixed $e):bool
    {
        if(!is_array($e)||($e['schema']??null)!==3||!array_key_exists('composicao',$e)||!is_bool($e['indisponivel']??null)||!in_array($e['origem']??null,['usuario','legado','perfil'],true))return false;
        foreach(['contexto','catalogo_chave'] as $k)if(!is_string($e[$k]??null)||!preg_match('/^[a-f0-9]{64}$/D',$e[$k]))return false;
        $d=$e['catalogo']??null;if(!is_array($d)||!is_array($d['sections']??null)||!array_is_list($d['sections'])||count($d['sections'])>20||!is_array($d['widgets']??null)||!array_is_list($d['widgets'])||count($d['widgets'])>64)return false;
        $sections=[];$widgets=[];
        foreach($d['sections'] as $n){if(!is_array($n)||!is_string($n['id']??null)||!preg_match('/^[a-z][a-z0-9-]{0,79}$/D',$n['id'])||isset($sections[$n['id']])||!self::texto($n['title']??null,60))return false;$sections[$n['id']]=$n;}
        foreach($d['widgets'] as $n){
            if(!is_array($n)||!is_string($n['id']??null)||!preg_match('/^[a-z][a-z0-9-]{0,79}$/D',$n['id'])||isset($widgets[$n['id']])||isset($sections[$n['id']])||!self::texto($n['title']??null,80)||!is_string($n['type']??null)||!is_string($n['parent']??null)||!isset($sections[$n['parent']]))return false;
            foreach(['w','h','minW','maxW','minH','maxH'] as $k)if(!is_int($n[$k]??null))return false;
            if($n['minW']<1||$n['maxW']>12||$n['minW']>$n['maxW']||$n['minH']<1||$n['maxH']>400||$n['minH']>$n['maxH']||$n['w']<$n['minW']||$n['w']>$n['maxW']||$n['h']<$n['minH']||$n['h']>$n['maxH'])return false;
            $widgets[$n['id']]=$n;
        }
        if(!self::documento($e['padrao']??null,$sections,$widgets))return false;
        return $e['indisponivel']?($e['composicao']??null)===null:self::documento($e['composicao']??null,$sections,$widgets);
    }

    private static function documento($s,array $defs,array $catalogo):bool
    {
        if(!is_array($s)||($s['schema']??null)!==3||array_diff(array_keys($s),['schema','sections','widgets'])||!is_array($s['sections']??null)||!array_is_list($s['sections'])||count($s['sections'])<1||count($s['sections'])>15||!is_array($s['widgets']??null)||count($s['widgets'])!==count($catalogo))return false;
        $sections=[];$nodes=[];
        foreach($s['sections'] as $n){
            if(!is_array($n)||!is_string($n['id']??null)||isset($sections[$n['id']])||isset($catalogo[$n['id']])||(!isset($defs[$n['id']])&&!preg_match('/^pessoal-[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$n['id']))||($n['kind']??null)!=='section'||!self::texto($n['title']??null,60)||!self::texto($n['originalTitle']??null,60))return false;
            if(array_diff(array_keys($n),['id','kind','title','originalTitle','parent','x','y','w','h','fixo','headerRows'])||array_key_exists('headerRows',$n)&&(!is_int($n['headerRows'])||$n['headerRows']<3||$n['headerRows']>8))return false;
            $sections[$n['id']]=$n;$nodes[]=$n;
        }
        foreach($s['widgets'] as $id=>$n){if(!isset($catalogo[$id])||!is_array($n)||($n['id']??null)!==$id||($n['kind']??null)!=='widget'||!self::texto($n['title']??null,80)||!is_bool($n['active']??null)||!in_array($n['orientacao']??null,['horizontal','vertical'],true)||array_diff(array_keys($n),['id','kind','title','parent','active','fixo','x','y','w','h','orientacao']))return false;$nodes[]=$n;}
        foreach($nodes as $n)if(!array_key_exists('parent',$n)||$n['parent']!==null&&(!is_string($n['parent'])||!isset($sections[$n['parent']])))return false;
        foreach($nodes as $n){
            $p=$n;$seen=[];$level=$n['kind']==='section'?1:0;
            while($p['parent']!==null){if(isset($seen[$p['parent']])||++$level>2)return false;$seen[$p['parent']]=true;$p=$sections[$p['parent']];}
            foreach(['x','y','w','h'] as $k)if(!is_int($n[$k]??null))return false;if(!is_bool($n['fixo']??null))return false;
            $b=$n['kind']==='section'?['minW'=>4,'maxW'=>12,'minH'=>4,'maxH'=>400]:$catalogo[$n['id']];
            if($n['x']<0||$n['y']<0||$n['w']<$b['minW']||$n['w']>$b['maxW']||$n['x']+$n['w']>12||$n['h']<$b['minH']||$n['h']>$b['maxH']||$n['y']+$n['h']>400)return false;
            if($n['parent']!==null&&($n['kind']==='section'||$n['active'])&&$n['y']+$n['h']>$sections[$n['parent']]['h']-($sections[$n['parent']]['headerRows']??3))return false;
        }
        $placed=[];foreach($nodes as $n)if($n['kind']==='section'||$n['active']){foreach($placed as $p)if($n['parent']===$p['parent']&&$n['x']<$p['x']+$p['w']&&$n['x']+$n['w']>$p['x']&&$n['y']<$p['y']+$p['h']&&$n['y']+$n['h']>$p['y'])return false;$placed[]=$n;}
        return true;
    }

    public static function projetar(array $e):array
    {
        $r=array_intersect_key($e,array_flip(['schema','contexto','catalogo_chave','padrao','composicao','indisponivel','origem']));
        $r['catalogo']=['sections'=>array_map(fn($n)=>array_intersect_key($n,array_flip(['id','title'])),$e['catalogo']['sections']),
            'widgets'=>array_map(fn($n)=>array_intersect_key($n,array_flip(['id','title','type','parent','w','h','minW','maxW','minH','maxH'])),$e['catalogo']['widgets'])];return $r;
    }
}
