<?php

namespace App\Support;

/** Ordem visual somente: nunca concede widgets, seções ou acesso a dados. */
final class OrganizacaoPainel
{
    public const CAMPOS = ['ordem_card','ordem_secao'];

    public static function grupos(string $painel): array
    {
        return $painel === 'turma' ? [
            'resultados'=>['estudantes','periodo-agenda','analises'],
            'acompanhamento'=>['planejamento','ensino','decisoes'],
            'administrativo'=>['administrativo'],
        ] : [];
    }

    public static function secao(string $id, string $secao, array $catalogo): string
    {
        // Alias estrito para os dois widgets que mudaram de grupo na nova composição.
        if ($secao==='planejamento' && in_array($id,['periodos-escolares','agenda-pedagogica'],true)
            && ($catalogo[$id]['section']??null)==='periodo-agenda') return 'periodo-agenda';
        return $secao;
    }

    public static function projetar(array $widgets, array $catalogo): array
    {
        $porId=array_column($catalogo,null,'id');
        foreach($widgets as &$w) if(isset($porId[$w['id']??null])) $w['section']=self::secao($w['id'],$w['section'],$porId);
        unset($w);
        return $widgets;
    }

    public static function valida(array $widgets, string $painel): bool
    {
        $porSecao=[];
        foreach(self::grupos($painel) as $card=>$secoes)foreach($secoes as $secao)$porSecao[$secao]=$card;
        $cards=[];$secoes=[];
        foreach($widgets as $w){
            $tem=array_intersect(self::CAMPOS,array_keys($w));
            if(!$tem)continue;
            if(count($tem)!==2 || !isset($porSecao[$w['section']]))return false;
            foreach(self::CAMPOS as $k)if(!is_int($w[$k]) || $w[$k]<0 || $w[$k]>63)return false;
            $card=$porSecao[$w['section']];$secao=$w['section'];
            if(isset($cards[$card]) && $cards[$card]!==$w['ordem_card'])return false;
            if(isset($secoes[$secao]) && $secoes[$secao]!==$w['ordem_secao'])return false;
            $cards[$card]=$w['ordem_card'];$secoes[$secao]=$w['ordem_secao'];
        }
        if(count(array_unique($cards))!==count($cards))return false;
        foreach(self::grupos($painel) as $lista){
            $ordens=array_intersect_key($secoes,array_flip($lista));
            if(count(array_unique($ordens))!==count($ordens))return false;
        }
        return true;
    }
}
