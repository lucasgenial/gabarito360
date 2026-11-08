<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{ConsultasPedagogicas,Permissao};
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Agregados e acompanhamento nominal no mesmo recorte autorizado, com permissões próprias para nomes/resultados. */
final class AnalisePainelTurma
{
    public function montar(Usuario $u,ConsultasPedagogicas $consulta,Builder $notas,Collection $provas,int $total,?int $selecao,Collection $resultadosAgrupados):array
    {
        abort_if($selecao && !$provas->contains('id',$selecao),422,'Avaliação fora do recorte autorizado.');
        $valores=(clone $notas)->whereRaw('notas.total_questoes > 0 AND notas.acertos >= 0 AND notas.acertos <= notas.total_questoes')
            ->select('notas.prova_id')->selectRaw('100.0 * notas.acertos / notas.total_questoes as x');
        $base=fn()=>DB::query()->fromSub(clone $valores,'valores');
        $agregado='COUNT(*) as n, AVG(x) as media, MIN(x) as minimo, MAX(x) as maximo, AVG(x*x) as quadrados';
        $global=$base()->selectRaw($agregado)->first();
        $grupos=$base()->select('prova_id')->selectRaw($agregado)->groupBy('prova_id')->get()->keyBy('prova_id');
        $mediana=$this->medianas(clone $valores,false)->first()?->mediana;
        $medianas=$this->medianas(clone $valores,true)->keyBy('prova_id');
        $expressoes=[];
        for($i=0;$i<10;$i++)$expressoes[]='SUM(CASE WHEN x >= '.($i*10).' AND x '.($i===9?'<=':'<').' '.(($i+1)*10).' THEN 1 ELSE 0 END) as f'.$i;
        $hist=$base()->selectRaw(implode(', ',$expressoes))->first();$faixas=[];
        for($i=0;$i<10;$i++)$faixas[]=['de'=>$i*10,'ate'=>($i+1)*10,'label'=>($i*10).'–'.($i===9?'100%':'<'.(($i+1)*10).'%'),'total'=>(int)($hist->{'f'.$i}??0)];
        $porAvaliacao=$provas->map(function($p)use($grupos,$medianas,$resultadosAgrupados){
            // Mesma agregação já calculada para os KPIs: não consulta nem soma disciplinas por instrumento.
            $contagem=$resultadosAgrupados->get($p->id);$registrados=(int)($contagem?->corrigidos??0);
            return ['avaliacao_id'=>(int)$p->id,'titulo'=>$p->titulo]+$this->resumo($grupos->get($p->id),$medianas->get($p->id)?->mediana)
                +['resultados_total'=>$registrados,'percentuais_excluidos'=>$registrados-(int)($contagem?->validos??0),'abrangencia'=>'avaliacao_completa'];
        })->values()->all();
        return ['resumo'=>$this->resumo($global,$mediana),
            'distribuicao_acertos'=>['faixas'=>$faixas,'total'=>(int)$global->n,'percentuais_excluidos'=>$total-(int)$global->n],
            'alunos'=>$this->alunos($u,$notas),'por_avaliacao'=>$porAvaliacao,'questoes'=>$this->questoes($u,$consulta,$notas,$provas,$selecao)];
    }

    private function alunos(Usuario $u,Builder $notas):array
    {
        $permitido=Permissao::pode($u,'aluno.ver')&&Permissao::pode($u,'resultado.ver')&&!in_array($u->perfil,['aluno','aplicador']);
        $vazio=['total'=>0,'items'=>[]];
        if(!$permitido)return ['disponivel'=>false,'atencao'=>$vazio,'dentro_criterio'=>$vazio,'destaques'=>$vazio+['empate_no_corte'=>false]];
        $q=(clone $notas)->join('alunos as painel_pessoa',function($j){$j->on('painel_pessoa.id','=','notas.aluno_id');if(\App\Support\Espacos::ativo())$j->on('painel_pessoa.espaco_id','=','notas.espaco_id');})
            ->select('notas.aluno_id','painel_pessoa.nome')->groupBy('notas.aluno_id','painel_pessoa.nome')
            ->selectRaw("COUNT(*) as resultados, SUM(CASE WHEN notas.status_aprovacao = 'recuperacao' THEN 1 ELSE 0 END) as recuperacao, SUM(CASE WHEN notas.status_aprovacao = 'aprovado' THEN 1 ELSE 0 END) as aprovados, SUM(CASE WHEN notas.total_questoes > 0 AND notas.acertos >= 0 AND notas.acertos <= notas.total_questoes THEN 1 ELSE 0 END) as base_valida, AVG(CASE WHEN notas.total_questoes > 0 AND notas.acertos >= 0 AND notas.acertos <= notas.total_questoes THEN 100.0*notas.acertos/notas.total_questoes ELSE NULL END) as media");
        $todos=$q->get()->map(fn($a)=>['aluno_id'=>(int)$a->aluno_id,'nome'=>$a->nome,'resultados'=>(int)$a->resultados,'base_valida'=>(int)$a->base_valida,'recuperacao'=>(int)$a->recuperacao,'aprovados'=>(int)$a->aprovados,'media_pct'=>$a->media===null?null:round((float)$a->media,1)]);
        $ordenar=fn($a,$b)=>strcmp(mb_strtolower($a['nome']),mb_strtolower($b['nome']))?:$a['aluno_id']<=>$b['aluno_id'];
        $atencao=$todos->filter(fn($a)=>$a['recuperacao']>0)->sort(fn($a,$b)=>$b['recuperacao']<=>$a['recuperacao']?:($a['media_pct']??101)<=>($b['media_pct']??101)?:$ordenar($a,$b))->values();
        $criterio=$todos->filter(fn($a)=>$a['resultados']>0&&$a['aprovados']===$a['resultados'])->sort($ordenar)->values();
        $ranking=$todos->filter(fn($a)=>$a['base_valida']>0)->sort(fn($a,$b)=>$b['media_pct']<=>$a['media_pct']?:$ordenar($a,$b))->values();
        $anterior=null;$posicao=0;$items=[];foreach($ranking as$i=>$a){if($anterior===null||$a['media_pct']!==$anterior)$posicao=$i+1;$anterior=$a['media_pct'];$a['posicao']=$posicao;if($i<5)$items[]=$a;}
        return ['disponivel'=>true,'base'=>'resultados_finais_da_turma','atencao'=>['total'=>$atencao->count(),'items'=>$atencao->all()],'dentro_criterio'=>['total'=>$criterio->count(),'items'=>$criterio->all()],'destaques'=>['total'=>$ranking->count(),'items'=>$items,'empate_no_corte'=>$ranking->count()>5&&$ranking[4]['media_pct']===$ranking[5]['media_pct']]];
    }

    private function medianas(Builder $valores,bool $porProva):Collection
    {
        $particao=$porProva?'PARTITION BY prova_id ':'';
        $ordem=DB::query()->fromSub($valores,'valores')->select('prova_id','x')
            ->selectRaw("ROW_NUMBER() OVER ({$particao}ORDER BY x) as posicao, COUNT(*) OVER ({$particao}) as quantidade");
        $q=DB::query()->fromSub($ordem,'ordenados')->whereRaw('2 * posicao >= quantidade AND 2 * posicao <= quantidade + 2');
        if($porProva)$q->select('prova_id')->groupBy('prova_id');
        return $q->selectRaw('AVG(x) as mediana')->get();
    }

    private function resumo(?object $g,mixed $mediana):array
    {
        $n=(int)($g?->n??0);$media=$n?(float)$g->media:null;
        return ['n'=>$n,'media_pct'=>$n?round($media,1):null,'mediana_pct'=>$mediana!==null?round((float)$mediana,1):null,
            'min_pct'=>$n?round((float)$g->minimo,1):null,'max_pct'=>$n?round((float)$g->maximo,1):null,
            'desvio_pct'=>$n?round(sqrt(max(0,(float)$g->quadrados-$media*$media)),1):null];
    }

    private function questoes(Usuario $u,ConsultasPedagogicas $consulta,Builder $notas,Collection $provas,?int $selecao):array
    {
        $permitido=Permissao::pode($u,'relatorio.prova')&&!in_array($u->perfil,['aluno','aplicador']);
        $opcoes=$permitido?$provas->map(fn($p)=>['id'=>(int)$p->id,'titulo'=>$p->titulo])->values()->all():[];
        $p=$permitido?($selecao?$provas->firstWhere('id',$selecao):$provas->first()):null;
        $r=['disponivel'=>$permitido,'avaliacao_id'=>$p?(int)$p->id:null,'opcoes'=>$opcoes,'titulo'=>$p?->titulo,
            'base'=>'resultados_finais_da_turma','lingua'=>'conforme_opcao_do_cartao','items'=>[]];
        if(!$p)return $r;
        $questoes=$consulta->tabela('gabarito_questoes')->join('gabaritos as painel_gabarito','painel_gabarito.id','=','gabarito_questoes.gabarito_id')
            ->where('painel_gabarito.prova_id',$p->id)->orderBy('gabarito_questoes.numero_questao')->get(['gabarito_questoes.*']);
        if($questoes->isEmpty())return $r;
        // Uma linha por resultado×questão, todas da mesma prova/turma autorizada.
        // Não usa o agregado geral das demais turmas nem carrega N×Q respostas em PHP.
        $finais=(clone $notas)->where('notas.prova_id',$p->id)->select('notas.cartao_id');
        $respostas=DB::query()->fromSub($finais,'finais')->join('cartoes as c','c.id','=','finais.cartao_id')
            ->join('gabaritos as g',fn($j)=>$j->on('g.prova_id','=','c.prova_id')->on('g.espaco_id','=','c.espaco_id'))
            ->join('gabarito_questoes as q',fn($j)=>$j->on('q.gabarito_id','=','g.id')->on('q.espaco_id','=','g.espaco_id'))
            ->leftJoin('cartao_respostas as a',fn($j)=>$j->on('a.cartao_id','=','c.id')->on('a.espaco_id','=','c.espaco_id')->on('a.numero_questao','=','q.numero_questao'));
        $esperada=$p->opcao_lingua?"CASE WHEN c.lingua = 'ESP' AND q.alternativa_espanhol IS NOT NULL AND q.alternativa_espanhol <> '' THEN q.alternativa_espanhol ELSE q.alternativa END":'q.alternativa';
        $lingua=$p->opcao_lingua?"COALESCE(c.lingua, 'NAO_INFORMADA')":"'UNICA'";
        $grupos=$respostas->select('q.numero_questao')->selectRaw("$lingua as lingua, COUNT(*) as base, "
            ."SUM(CASE WHEN q.anulada = 0 AND a.ambigua = 0 AND a.alternativa = $esperada THEN 1 ELSE 0 END) as acertos, "
            ."SUM(CASE WHEN a.id IS NULL THEN 1 ELSE 0 END) as sem_registro, "
            ."SUM(CASE WHEN a.id IS NOT NULL AND a.ambigua = 0 AND a.alternativa IS NULL THEN 1 ELSE 0 END) as brancas, "
            ."SUM(CASE WHEN a.ambigua = 1 THEN 1 ELSE 0 END) as ambiguas, "
            ."SUM(CASE WHEN a.ambigua = 0 AND a.alternativa IS NOT NULL AND a.alternativa <> $esperada THEN 1 ELSE 0 END) as incorretas")
            ->groupBy('q.numero_questao')->groupByRaw($lingua)->get()->groupBy('numero_questao');
        foreach($questoes as$q){
            $gs=$grupos->get($q->numero_questao,collect());$n=(int)$gs->sum('base');$acertos=(int)$gs->sum('acertos');
            $r['items'][]=['numero'=>(int)$q->numero_questao,'acertos'=>$acertos,'base'=>$n,'pct'=>!$q->anulada&&$n?round(100*$acertos/$n,1):null,
                'anulada'=>(bool)$q->anulada,'sem_registro'=>(int)$gs->sum('sem_registro'),'brancas'=>(int)$gs->sum('brancas'),
                'incorretas'=>(int)$gs->sum('incorretas'),'ambiguas'=>(int)$gs->sum('ambiguas'),
                'por_lingua'=>$gs->map(fn($g)=>['lingua'=>$g->lingua,'base'=>(int)$g->base,'acertos'=>(int)$g->acertos,'pct'=>!$q->anulada&&$g->base?round(100*$g->acertos/$g->base,1):null])->sortBy('lingua')->values()->all()];
        }
        return $r;
    }
}
