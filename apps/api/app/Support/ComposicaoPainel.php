<?php
namespace App\Support;

use Illuminate\Validation\ValidationException;

/** Documento de apresentação. IDs autorizados vêm do catálogo, nunca do cliente. */
final class ComposicaoPainel
{
    public const MAX_ROWS=400;
    public const MAX_SECOES=15;
    private static function erro(string $mensagem='Composição de painel inválida.'): never
    { throw ValidationException::withMessages(['composicao'=>$mensagem]); }

    public static function definicoes(string $painel, array $catalogo): array
    {
        $nomes=[
            'turmas-lista'=>'Turmas','cadastros-turno'=>'Turmas por turno','comparacao-turmas'=>'Resultados entre turmas',
            'cobertura-disciplinas'=>'Cobertura por disciplina','proximos-compromissos'=>'Agenda de avaliações',
            'devolutivas'=>'Devolutivas','responsabilidade-docente-aplicacao'=>'Responsabilidade docente na aplicação',
            'prioridades'=>'Prioridades de acompanhamento','intervencoes'=>'Ações de acompanhamento',
            'agenda-administrativa'=>'Agenda administrativa','documentos-administrativos'=>'Documentos administrativos',
            'alunos-destaques'=>'Destaques da turma','alunos-atencao'=>'Alunos que precisam de atenção',
            'distribuicao-acertos'=>'Distribuição dos acertos','resultados-avaliacao'=>'Resultados por avaliação',
            'situacoes-registradas'=>'Situações registradas','periodos-escolares'=>'Resultados por período escolar',
            'agenda-pedagogica'=>'Agenda da turma','participacao'=>'Participação nas aplicações',
            'habilidades'=>'Habilidades em acompanhamento','reavaliacoes'=>'Resultados das reavaliações',
            'metas'=>'Metas de acompanhamento','conselho'=>'Encaminhamentos do conselho',
        ];
        $secoes=$painel==='turmas' ? [
            ['id'=>'cadastros','title'=>'Turmas'],['id'=>'analises','title'=>'Análises entre turmas'],['id'=>'acompanhamento','title'=>'Acompanhamento pedagógico'],
        ] : [
            ['id'=>'resultados','title'=>'Resultados da turma'],['id'=>'acompanhamento','title'=>'Acompanhamento da turma'],['id'=>'planejamento','title'=>'Planejamento da turma'],
        ];
        $extras=['estudantes'=>'Estudantes','periodo-agenda'=>'Período e agenda','analises'=>'Análises da turma','ensino'=>'Ensino e acompanhamento','administrativo'=>'Organização administrativa'];
        if($painel==='turma')foreach($extras as $id=>$title)$secoes[]=['id'=>$id,'title'=>$title];
        $secoes[]=['id'=>'grupo-planejamento','title'=>'Planejamento'];
        if($painel==='turmas')foreach(['grupo-ensino'=>'Ensino e acompanhamento','grupo-administrativo'=>'Organização administrativa'] as $id=>$title)$secoes[]=['id'=>$id,'title'=>$title];
        $widgets=[];
        foreach($catalogo as $item){
            $id=$item['id'];$grupo=$item['section'];$b=$item['desktop'];
            if(!isset($nomes[$id]))self::erro('O catálogo do painel precisa ser atualizado.');
            $parent=$painel==='turmas'?($grupo==='analises'?'analises':match($grupo){'planejamento'=>'grupo-planejamento','ensino'=>'grupo-ensino',default=>'grupo-administrativo'}):(in_array($grupo,['estudantes','periodo-agenda','analises','ensino','administrativo'],true)?$grupo:($grupo==='decisoes'?'planejamento':'grupo-planejamento'));
            $widgets[]=['id'=>$id,'title'=>$nomes[$id],'type'=>$id,'parent'=>$parent,'w'=>$b['defaultW'],'h'=>$b['defaultH'],'minW'=>$b['minW'],'maxW'=>$b['maxW'],'minH'=>$b['minH'],'maxH'=>$b['maxH']];
        }
        if($painel==='turmas')array_unshift($widgets,['id'=>'turmas-lista','title'=>'Turmas','type'=>'lista','parent'=>'cadastros','w'=>12,'h'=>12,'minW'=>6,'maxW'=>12,'minH'=>8,'maxH'=>40]);
        return ['sections'=>$secoes,'widgets'=>$widgets];
    }

    public static function chave(array $defs): string { return hash('sha256',json_encode($defs,JSON_THROW_ON_ERROR)); }

    private static function texto(mixed $v, int $max): bool
    { return is_string($v)&&trim($v)!==''&&mb_strlen($v)<=$max&&!preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u',$v); }

    private static function colisao(array $a,array $b): bool
    { return $a['x']<$b['x']+$b['w']&&$a['x']+$a['w']>$b['x']&&$a['y']<$b['y']+$b['h']&&$a['y']+$a['h']>$b['y']; }

    public static function validar(mixed $s,array $defs): array
    {
        if(!is_array($s)||array_diff(array_keys($s),['schema','sections','widgets'])||($s['schema']??null)!==3
            ||!is_array($s['sections']??null)||!array_is_list($s['sections'])||count($s['sections'])<1||count($s['sections'])>15
            ||!is_array($s['widgets']??null)||count($s['widgets'])!==count($defs['widgets']))self::erro();
        $catalogo=array_column($defs['widgets'],null,'id');$secoes=[];$ids=[];
        foreach($s['sections'] as $n){
            if(!is_array($n)||!is_string($n['id']??null)||isset($ids[$n['id']])||isset($catalogo[$n['id']])
                ||(!in_array($n['id'],array_column($defs['sections'],'id'),true)&&!preg_match('/^pessoal-[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$n['id'])))self::erro();
            if(array_diff(array_keys($n),['id','kind','title','originalTitle','parent','x','y','w','h','fixo','headerRows'])
                ||($n['kind']??null)!=='section'||!self::texto($n['originalTitle']??null,60)||!self::texto($n['title']??null,60))self::erro();
            if(array_key_exists('headerRows',$n)&&(!is_int($n['headerRows'])||$n['headerRows']<3||$n['headerRows']>8))self::erro();
            $secoes[$n['id']]=$n;$ids[$n['id']]=true;
        }
        $widgets=[];
        foreach($s['widgets'] as $id=>$n){
            if(!isset($catalogo[$id])||!is_array($n)||($n['id']??null)!==$id||($n['kind']??null)!=='widget'
                ||array_diff(array_keys($n),['id','kind','title','parent','active','fixo','x','y','w','h','orientacao'])
                ||!self::texto($n['title']??null,80)||!is_bool($n['active']??null)||!in_array($n['orientacao']??null,['horizontal','vertical'],true))self::erro();
            $widgets[$id]=$n;
        }
        $todos=array_merge($secoes,$widgets);
        // Validar todas as arestas antes de percorrer um ancestral que ainda não foi visitado.
        foreach($todos as $n)if(!array_key_exists('parent',$n)||$n['parent']!==null&&(!is_string($n['parent'])||!isset($secoes[$n['parent']])))self::erro();
        foreach($todos as $id=>$n){
            $vistos=[];$p=$n;$nivel=$n['kind']==='section'?1:0;
            while($p['parent']!==null){if(isset($vistos[$p['parent']]))self::erro('A composição contém um ciclo.');$vistos[$p['parent']]=true;$p=$secoes[$p['parent']];if(++$nivel>2)self::erro('Use no máximo dois níveis de seções.');}
            if(!is_bool($n['fixo']??null))self::erro();
            foreach(['x','y','w','h'] as $k)if(!is_int($n[$k]??null))self::erro();
            $b=$n['kind']==='section'?['minW'=>4,'maxW'=>12,'minH'=>4,'maxH'=>self::MAX_ROWS]:$catalogo[$id];
            if($n['x']<0||$n['y']<0||$n['w']<$b['minW']||$n['w']>$b['maxW']||$n['x']+$n['w']>12
                ||$n['h']<$b['minH']||$n['h']>$b['maxH']||$n['y']+$n['h']>self::MAX_ROWS)self::erro();
        }
        foreach([null,...array_keys($secoes)] as $parent){
            $itens=array_values(array_filter($todos,fn($n)=>$n['parent']===$parent&&($n['kind']==='section'||$n['active'])));
            foreach($itens as $i=>$n){
                foreach(array_slice($itens,0,$i) as $a)if(self::colisao($a,$n))self::erro('Componentes não podem se sobrepor.');
                if($parent!==null&&$n['y']+$n['h']>$secoes[$parent]['h']-($secoes[$parent]['headerRows']??3))self::erro('A seção corta o conteúdo de um componente.');
            }
        }
        return $s;
    }

    /** Empacotamento determinístico usado somente na projeção/migração, sem mover pins. */
    private static function normalizar(array $s,array $defs): array
    {
        $ordenar=static fn($a,$b)=>[$a['y'],$a['x'],$a['id']]<=>[$b['y'],$b['x'],$b['id']];
        $grupo=function(?string $pai)use(&$s,$ordenar):void{
            $refs=[];foreach($s['sections'] as $i=>$n)if($n['parent']===$pai)$refs[]=['tipo'=>'sections','key'=>$i,'n'=>$n];
            foreach($s['widgets'] as $id=>$n)if($n['parent']===$pai&&$n['active'])$refs[]=['tipo'=>'widgets','key'=>$id,'n'=>$n];
            usort($refs,fn($a,$b)=>$ordenar($a['n'],$b['n']));$postos=[];
            foreach($refs as $r)if($r['n']['fixo']){foreach($postos as $p)if(self::colisao($p,$r['n']))self::erro('Há posições fixadas incompatíveis.');$postos[]=$r['n'];}
            foreach($refs as $r){$n=$r['n'];if($n['fixo'])continue;$livre=fn($caixa)=>!array_filter($postos,fn($p)=>self::colisao($p,$caixa));
                if(!$livre($n)){$achou=false;for($y=0;$y+$n['h']<=self::MAX_ROWS&&!$achou;$y++)for($x=0;$x+$n['w']<=12;$x++){$caixa=array_replace($n,compact('x','y'));if($livre($caixa)){$n=$caixa;$achou=true;break;}}if(!$achou)self::erro('Não há espaço na composição.');}
                $s[$r['tipo']][$r['key']]=$n;$postos[]=$n;
            }
        };
        // A profundidade máxima é dois: primeiro seções filhas, depois raízes.
        foreach([false,true] as $raiz)foreach($s['sections'] as $i=>$secao)if(($secao['parent']===null)===$raiz){
            $grupo($secao['id']);$bottom=3;foreach([...$s['sections'],...array_values($s['widgets'])] as $n)if($n['parent']===$secao['id']&&($n['kind']==='section'||$n['active']))$bottom=max($bottom,$n['y']+$n['h']);
            $h=max($secao['h'],($secao['headerRows']??3)+$bottom);if($secao['fixo']&&$h>$secao['h'])self::erro('Uma seção fixada impede a reconciliação.');$s['sections'][$i]['h']=$h;
        }
        $grupo(null);return self::validar($s,$defs);
    }

    public static function padrao(string $painel,array $defs,array $legado=[]): array
    {
        $s=['schema'=>3,'sections'=>[],'widgets'=>[]];$rootIds=$painel==='turmas'?['cadastros','analises','acompanhamento']:['resultados','acompanhamento','planejamento'];
        $necessarias=array_fill_keys($rootIds,true);foreach($defs['widgets'] as $w)$necessarias[$w['parent']]=true;
        foreach($defs['sections'] as $n)if(isset($necessarias[$n['id']])){$parent=in_array($n['id'],$rootIds,true)?null:($painel==='turma'&&in_array($n['id'],['estudantes','periodo-agenda','analises'],true)?'resultados':($n['id']==='administrativo'?'planejamento':'acompanhamento'));$s['sections'][]=$n+['originalTitle'=>$n['title'],'kind'=>'section','parent'=>$parent,'x'=>0,'y'=>0,'w'=>12,'h'=>6,'fixo'=>false];}
        $old=array_column($legado,null,'id');$cursores=[];
        foreach($defs['widgets'] as $d){$w=$old[$d['id']]??null;$parent=$d['parent'];$cursor=$cursores[$parent]??['x'=>0,'y'=>0,'row'=>0];if($cursor['x']+$d['w']>12)$cursor=['x'=>0,'y'=>$cursor['y']+$cursor['row'],'row'=>0];$n=['id'=>$d['id'],'kind'=>'widget','title'=>$d['title'],'parent'=>$parent,'active'=>true,'fixo'=>$w['fixo']??false,'x'=>$w['x']??$cursor['x'],'y'=>$w['y']??$cursor['y'],'w'=>$w['w']??$d['w'],'h'=>$w['h']??$d['h'],'orientacao'=>$w['orientacao']??'vertical'];$s['widgets'][$d['id']]=$n;$cursores[$parent]=['x'=>$cursor['x']+$d['w'],'y'=>$cursor['y'],'row'=>max($cursor['row'],$d['h'])];}
        $porSecao=array_column($s['sections'],null,'id');$ranksRoot=[];$ranksSecao=[];
        foreach($legado as $w)if(isset($s['widgets'][$w['id']])&&isset($w['ordem_card'],$w['ordem_secao'])){
            $pai=$s['widgets'][$w['id']]['parent'];$raiz=$pai;while($porSecao[$raiz]['parent']!==null)$raiz=$porSecao[$raiz]['parent'];
            $ranksRoot[$raiz]=min($ranksRoot[$raiz]??PHP_INT_MAX,$w['ordem_card']);$ranksSecao[$pai]=min($ranksSecao[$pai]??PHP_INT_MAX,$w['ordem_secao']);
        }
        foreach([null,...$rootIds] as $pai){$indices=array_keys(array_filter($s['sections'],fn($n)=>$n['parent']===$pai));
            usort($indices,function($a,$b)use($s,$pai,$ranksRoot,$ranksSecao){$r=$pai===null?$ranksRoot:$ranksSecao;return [$r[$s['sections'][$a]['id']]??$a,$a]<=>[$r[$s['sections'][$b]['id']]??$b,$b];});
            foreach($indices as $ordem=>$i)$s['sections'][$i]['y']=$ordem;
        }
        return self::normalizar($s,$defs);
    }

    /** A projeção não regrava o documento, nem publica widgets revogados. */
    public static function projetar(?array $stored,string $painel,array $defs,array $legado=[]): array
    {
        $padrao=self::padrao($painel,$defs,$legado);if($stored===null)return $padrao;
        $s=$stored;$allowed=array_fill_keys(array_column($defs['widgets'],'id'),true);$s['widgets']=array_intersect_key($s['widgets'],$allowed);
        foreach($padrao['widgets'] as $id=>$w)if(!isset($s['widgets'][$id])){$w['parent']=null;$s['widgets'][$id]=$w;}
        // Conservar seções vazias criadas pelo usuário; esconder ramos que só têm conteúdo revogado.
        $mantem=function(string $id)use(&$mantem,$stored,$allowed):bool{$children=array_filter([...$stored['sections'],...array_values($stored['widgets'])],fn($n)=>$n['parent']===$id);if(!$children)return true;foreach($children as $n)if($n['kind']==='widget'?isset($allowed[$n['id']]):$mantem($n['id']))return true;return false;};
        $s['sections']=array_values(array_filter($s['sections'],fn($n)=>$mantem($n['id'])));
        if(!$s['sections'])$s['sections']=$padrao['sections'];
        $parents=array_fill_keys(array_column($s['sections'],'id'),true);foreach($s['widgets'] as &$w)if($w['parent']!==null&&!isset($parents[$w['parent']]))$w['parent']=null;unset($w);
        return self::normalizar($s,$defs);
    }

    /** Escrita nova preserva widgets dormentes; remover seção promove também seus filhos ocultos. */
    public static function mesclar(array $pedido,?array $anterior,array $defs,string $painel='turmas'): array
    {
        self::validar($pedido,$defs);if($anterior===null)return $pedido;
        $parents=array_fill_keys(array_column($pedido['sections'],'id'),true);$old=array_column($anterior['sections'],null,'id');
        $visivel=self::projetar($anterior,$painel,$defs);
        $antesVisivel=array_fill_keys(array_column($visivel['sections'],'id'),true);
        // Omissão por perda de capacidade não significa remoção deliberada do usuário.
        foreach($old as $id=>$n)if(!isset($parents[$id])){
            $p=$id;$removido=false;while($p!==null){if(isset($antesVisivel[$p])&&!isset($parents[$p])){$removido=true;break;}$p=$old[$p]['parent']??null;}
            if(!$removido){$pedido['sections'][]=$n;$parents[$id]=true;}
            elseif($n['fixo']&&!isset($antesVisivel[$id]))self::erro('Esta seção contém uma posição fixada preservada por outro acesso. Ajuste essa posição antes de remover a seção.');
        }
        foreach($anterior['widgets'] as $id=>$w)if(!isset($pedido['widgets'][$id])){
            $pai=$w['parent'];while($pai!==null&&!isset($parents[$pai]))$pai=$old[$pai]['parent']??null;
            if($pai!==$w['parent']){if($w['fixo'])self::erro('Esta seção contém uma posição fixada preservada por outro acesso. Ajuste essa posição antes de remover a seção.');$w['parent']=$pai;$w['x']=0;$w['y']=0;}$pedido['widgets'][$id]=$w;
        }
        if(count($pedido['sections'])>self::MAX_SECOES)self::erro('O limite de seções inclui a organização preservada de outros acessos. Remova uma seção disponível antes de criar outra.');
        // A autorização do pedido já foi validada acima. IDs acrescentados aqui vêm
        // exclusivamente do documento durável, nunca de campos livres do cliente.
        $integridade=$defs;$conhecidos=array_fill_keys(array_column($defs['widgets'],'id'),true);
        foreach($pedido['widgets'] as $id=>$w)if(!isset($conhecidos[$id]))$integridade['widgets'][]=['id'=>$id,'minW'=>1,'maxW'=>12,'minH'=>1,'maxH'=>self::MAX_ROWS];
        $secoesConhecidas=array_fill_keys(array_column($defs['sections'],'id'),true);
        foreach($pedido['sections'] as $s)if(isset($old[$s['id']])&&!isset($secoesConhecidas[$s['id']]))$integridade['sections'][]=['id'=>$s['id']];
        return self::validar($pedido,$integridade);
    }
}
