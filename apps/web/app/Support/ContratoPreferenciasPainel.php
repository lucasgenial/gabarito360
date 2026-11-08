<?php
namespace App\Support;

/** Valida o contrato de transporte. A autorização continua sendo decidida pela API. */
final class ContratoPreferenciasPainel
{
    /** Projeta somente os campos públicos, também nos níveis internos. */
    public static function projetar(array $d): array
    {
        $limites=['minW','maxW','minH','maxH','defaultW','defaultH'];
        $r=['versao'=>$d['versao'],'apresentacao'=>$d['apresentacao'],
            'layouts'=>array_combine(['desktop','mobile'],array_map(static fn($modo)=>array_map(
                static fn($r)=>array_intersect_key($r,array_flip(['id','section','x','y','w','h','altura_desejada','fixo','altura_livre','orientacao','ordem_card','ordem_secao'])),$d['layouts'][$modo]),['desktop','mobile'])),
            'catalogo'=>array_map(static fn($c)=>['id'=>$c['id'],'section'=>$c['section'],
                'desktop'=>array_intersect_key($c['desktop'],array_flip($limites)),
                'mobile'=>array_intersect_key($c['mobile'],array_flip($limites))],$d['catalogo']),
            'repetido'=>($d['repetido']??false)===true];
        if (array_key_exists('padrao',$d)) $r['padrao']=['desktop'=>array_map(
            static fn($w)=>array_intersect_key($w,array_flip(['id','section','x','y','w','h','altura_desejada','fixo','altura_livre','orientacao','ordem_card','ordem_secao'])),$d['padrao']['desktop'])];
        if (array_key_exists('origem_disposicao',$d)) $r['origem_disposicao']=$d['origem_disposicao'];
        return $r;
    }
    public static function valido(mixed $d): bool
    {
        if(!is_array($d)||!is_int($d['versao']??null)||$d['versao']<0||!in_array($d['apresentacao']??null,['tabela','cards'],true)
            ||!is_array($d['catalogo']??null)||!array_is_list($d['catalogo'])||count($d['catalogo'])>64||!is_array($d['layouts']??null))return false;
        $catalogo=[];
        foreach($d['catalogo'] as $c){
            if(!is_array($c)||!is_string($c['id']??null)||!preg_match('/^[a-z][a-z0-9-]{0,79}$/D',$c['id'])||isset($catalogo[$c['id']])
                ||!is_string($c['section']??null)||!preg_match('/^[a-z][a-z0-9-]{0,79}$/D',$c['section']))return false;
            foreach(['desktop','mobile'] as $modo){
                $b=$c[$modo]??null;if(!is_array($b))return false;
                foreach(['minW','maxW','minH','maxH','defaultW','defaultH'] as $k)if(!is_int($b[$k]??null))return false;
                if($b['minW']<1||$b['maxW']>12||$b['maxW']<$b['minW']||$b['minH']<1||$b['maxH']>200||$b['maxH']<$b['minH']
                    ||$b['defaultW']<$b['minW']||$b['defaultW']>$b['maxW']||$b['defaultH']<$b['minH']||$b['defaultH']>$b['maxH'])return false;
            }
            $catalogo[$c['id']]=$c;
        }
        if(array_key_exists('origem_disposicao',$d)&&!in_array($d['origem_disposicao'],['usuario','perfil'],true))return false;
        $mapas=['desktop'=>$d['layouts']['desktop']??null,'mobile'=>$d['layouts']['mobile']??null];
        if(array_key_exists('padrao',$d)){
            if(!is_array($d['padrao'])||!is_array($d['padrao']['desktop']??null))return false;
            $mapas['padrao']=$d['padrao']['desktop'];
        }
        foreach($mapas as $dispositivo=>$lista){
            $modo=$dispositivo==='padrao'?'desktop':$dispositivo;
            if(!is_array($lista)||!array_is_list($lista)||count($lista)>64)return false;
            $vistos=[];$anteriores=[];
            foreach($lista as $r){
                if(!is_array($r)||!is_string($r['id']??null)||!isset($catalogo[$r['id']])||isset($vistos[$r['id']]))return false;
                $c=$catalogo[$r['id']];$b=$c[$modo];if(($r['section']??null)!==$c['section'])return false;
                foreach(['x','y','w','h'] as $k)if(!is_int($r[$k]??null))return false;
                if($r['x']<0||$r['y']<0||$r['x']+$r['w']>12||$r['y']+$r['h']>200||$r['w']<$b['minW']||$r['w']>$b['maxW']||$r['h']<$b['minH']||$r['h']>$b['maxH'])return false;
                if(array_key_exists('altura_desejada',$r)&&(!is_int($r['altura_desejada'])||$r['altura_desejada']<$b['minH']||$r['altura_desejada']>$r['h']))return false;
                foreach(['fixo','altura_livre'] as $opcao)if(array_key_exists($opcao,$r)&&!is_bool($r[$opcao]))return false;
                if(array_key_exists('orientacao',$r)&&(!in_array($r['id'],['distribuicao-acertos','resultados-avaliacao','periodos-escolares','habilidades','reavaliacoes'],true)
                    ||!in_array($r['orientacao'],['horizontal','vertical'],true)))return false;
                foreach(['ordem_card','ordem_secao'] as $opcao)if(array_key_exists($opcao,$r)&&(!is_int($r[$opcao])||$r[$opcao]<0||$r[$opcao]>63))return false;
                foreach($anteriores as $a)if($a['section']===$r['section']&&$a['x']<$r['x']+$r['w']&&$r['x']<$a['x']+$a['w']&&$a['y']<$r['y']+$r['h']&&$r['y']<$a['y']+$a['h'])return false;
                $vistos[$r['id']]=true;$anteriores[]=$r;
            }
            if(!self::organizacaoValida($lista))return false;
        }
        return true;
    }

    private static function organizacaoValida(array $widgets): bool
    {
        $grupos=['resultados'=>['estudantes','periodo-agenda','analises'],'acompanhamento'=>['planejamento','ensino','decisoes'],'administrativo'=>['administrativo']];
        $porSecao=[];foreach($grupos as $card=>$secoes)foreach($secoes as $secao)$porSecao[$secao]=$card;
        $cards=[];$secoes=[];
        foreach($widgets as $w){
            $campos=array_intersect(['ordem_card','ordem_secao'],array_keys($w));if(!$campos)continue;
            if(count($campos)!==2 || !isset($porSecao[$w['section']]))return false;
            $card=$porSecao[$w['section']];$secao=$w['section'];
            if(isset($cards[$card])&&$cards[$card]!==$w['ordem_card'] || isset($secoes[$secao])&&$secoes[$secao]!==$w['ordem_secao'])return false;
            $cards[$card]=$w['ordem_card'];$secoes[$secao]=$w['ordem_secao'];
        }
        if(count(array_unique($cards))!==count($cards))return false;
        foreach($grupos as $lista){$ordens=array_intersect_key($secoes,array_flip($lista));if(count(array_unique($ordens))!==count($ordens))return false;}
        return true;
    }
}
