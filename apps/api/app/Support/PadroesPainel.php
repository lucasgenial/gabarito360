<?php
namespace App\Support;

/** Geometrias do perfil ativo somente. Os IDs já precisam estar autorizados pelo catálogo. */
final class PadroesPainel
{
    public static function desktop(string $perfil, string $painel, array $catalogo): array
    {
        $def=config('paineis.padroes.'.$perfil.'.'.$painel);
        abort_unless(is_array($def) && is_array($def['ordem']??null) && array_is_list($def['ordem'])
            && is_array($def['larguras']??null) && is_array($def['alturas_desejadas']??null),503);
        $porId=array_column($catalogo,null,'id');$ordenados=[];
        foreach ($def['ordem'] as $id) {
            abort_unless(is_string($id),503);
            if(isset($porId[$id]))$ordenados[$id]=$porId[$id];
        }
        // Um novo ID registrado aparece ao final, sem editar preferências pessoais existentes.
        foreach($porId as $id=>$item)if(!isset($ordenados[$id]))$ordenados[$id]=$item;
        $secoes=[];
        foreach($ordenados as $item)$secoes[$item['section']][]=$item;
        $resultado=[];
        foreach($secoes as $section=>$items){
            $linha=[];$ocupado=0;$y=0;
            $fechar=static function()use(&$linha,&$ocupado,&$y,&$resultado,$section):void {
                if(!$linha)return;
                // Preencher toda a linha sem reduzir mínimos ou ultrapassar máximos.
                $restante=12-$ocupado;
                while($restante>0){$mudou=false;foreach($linha as &$item){if($restante && $item['w']<$item['maxW']){$item['w']++;$restante--;$mudou=true;}}unset($item);abort_unless($mudou,503);}
                $h=max(array_column($linha,'altura_desejada'));abort_unless($y+$h<=200,503);$x=0;
                foreach($linha as $item){abort_unless($h<=$item['maxH'],503);$resultado[]=['id'=>$item['id'],'section'=>$section,'x'=>$x,'y'=>$y,'w'=>$item['w'],'h'=>$h,'altura_desejada'=>$item['altura_desejada']];$x+=$item['w'];}
                $y+=$h;$linha=[];$ocupado=0;
            };
            foreach($items as $c){
                $b=$c['desktop'];$w=$def['larguras'][$c['id']]??$b['defaultW'];$h=$def['alturas_desejadas'][$c['id']]??$b['defaultH'];
                abort_unless(is_int($w)&&is_int($h)&&$w>=$b['minW']&&$w<=$b['maxW']&&$h>=$b['minH']&&$h<=$b['maxH'],503);
                if($linha && ($ocupado+$w>12 || max($h,...array_column($linha,'altura_desejada'))>min($b['maxH'],...array_column($linha,'maxH'))))$fechar();
                $linha[]=['id'=>$c['id'],'w'=>$w,'maxW'=>$b['maxW'],'maxH'=>$b['maxH'],'altura_desejada'=>$h];$ocupado+=$w;
                if($ocupado===12)$fechar();
            }
            $fechar();
        }
        return $resultado;
    }
}
