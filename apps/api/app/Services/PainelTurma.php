<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{Escopo,ConsultasPedagogicas,Permissao,Cabecalho,PerfisEscolares};
use Illuminate\Support\Facades\DB;

/** DTO único para tela e documento; autorização antecede opções, filtros e agregados. */
final class PainelTurma
{
    public function montar(Usuario $u,int $id,array $filtros): array
    {
        abort_unless(Permissao::pode($u,'relatorio.turma')&&!PerfisEscolares::administrativo($u->perfil),403);
        $turma=Escopo::turmas($u)->findOrFail($id);
        $consulta=new ConsultasPedagogicas($u);
        $base=Escopo::provas($u)->whereIn('provas.id',$consulta->tabela('prova_turmas')->where('turma_id',$id)->select('prova_id'))
            ->whereIn('provas.status',['publicada','em_correcao','corrigida'])
            ->where(fn($q)=>$q->whereDate('provas.data_aplicacao','<=',now('America/Sao_Paulo')->toDateString())
                ->orWhere(fn($s)=>$s->whereNull('provas.data_aplicacao')->whereIn('provas.status',['em_correcao','corrigida'])));
        $disciplinas=DB::table('prova_disciplinas as pd')->joinSub((clone $base)->select('provas.id','provas.espaco_id'),'p',fn($j)=>$j->on('p.id','=','pd.prova_id')->on('p.espaco_id','=','pd.espaco_id'))
            ->join('disciplinas as d',fn($j)=>$j->on('d.id','=','pd.disciplina_id')->on('d.espaco_id','=','pd.espaco_id'))->select('d.id','d.nome')->distinct()->orderBy('d.nome')->get();
        // Autoria original da prova, nunca inferida a partir da atribuição docente atual.
        $autores=DB::table('usuarios as u')->joinSub((clone $base)->select('provas.criado_por','provas.perfil_criacao'),'p','p.criado_por','=','u.id')
            ->whereIn('p.perfil_criacao',['professor','professor_autonomo'])->select('u.id','u.nome')->distinct()->orderBy('u.nome')->get();
        if(!empty($filtros['disciplina_id'])) {
            abort_unless($disciplinas->contains('id',(int)$filtros['disciplina_id']),422,'Disciplina fora do recorte autorizado.');
            $base->whereIn('provas.id',DB::table('prova_disciplinas')->where('espaco_id',$turma->espaco_id)->where('disciplina_id',$filtros['disciplina_id'])->select('prova_id'));
        }
        if(!empty($filtros['professor_id'])) {
            abort_unless($autores->contains('id',(int)$filtros['professor_id']),422,'Professor autor fora do recorte autorizado.');
            $base->where('provas.criado_por',$filtros['professor_id'])->whereIn('provas.perfil_criacao',['professor','professor_autonomo']);
        }
        if(!empty($filtros['inicio']))$base->whereDate('provas.data_aplicacao','>=',$filtros['inicio']);
        if(!empty($filtros['fim']))$base->whereDate('provas.data_aplicacao','<=',$filtros['fim']);
        $provas=$base->orderBy('provas.data_aplicacao')->orderBy('provas.id')->get(['provas.id','provas.titulo','provas.disciplina','provas.data_aplicacao','provas.nota_maxima']);
        $agrupadas=$consulta->tabela('notas')->where('turma_id',$id)->whereIn('prova_id',$provas->pluck('id'))
            ->select('prova_id')->selectRaw('COUNT(*) as corrigidos, AVG(nota_final) as media, SUM(CASE WHEN total_questoes > 0 THEN 100.0 * acertos / total_questoes ELSE 0 END) as soma_pct, SUM(CASE WHEN total_questoes > 0 THEN 1 ELSE 0 END) as validos')
            ->groupBy('prova_id')->get()->keyBy('prova_id');
        $avaliacoes=$provas->map(function($p)use($agrupadas){
            $n=$agrupadas->get($p->id);$validos=(int)($n?->validos??0);
            return ['titulo'=>$p->titulo,'disciplina'=>$p->disciplina,'data_aplicacao'=>$p->data_aplicacao,'corrigidos'=>(int)($n?->corrigidos??0),'nota_maxima'=>(float)$p->nota_maxima,
                'media'=>$n?->media!==null?round((float)$n->media,1):null,'acerto_pct'=>$validos?round((float)$n->soma_pct/$validos,1):null];
        })->values()->all();
        $validos=(int)$agrupadas->sum('validos');
        return ['turma'=>['id'=>$turma->id,'nome'=>$turma->nome,'serie'=>$turma->serie,'ano_letivo'=>$turma->ano_letivo,'escola_id'=>$turma->escola_id],
            'cabecalho'=>Cabecalho::contexto($u,$turma->escola_id),'filtros'=>$filtros,
            'recorte'=>['disciplina'=>!empty($filtros['disciplina_id'])?$disciplinas->firstWhere('id',(int)$filtros['disciplina_id'])->nome:'Todas as disciplinas',
                'professor'=>!empty($filtros['professor_id'])?$autores->firstWhere('id',(int)$filtros['professor_id'])->nome:'Todos os autores autorizados'],
            'opcoes'=>['disciplinas'=>$disciplinas->all(),'professores'=>$autores->all()],
            'kpis'=>['alunos'=>$consulta->tabela('alunos')->where('turma_id',$id)->where('ativo',true)->count(),'avaliacoes'=>$provas->count(),'resultados'=>(int)$agrupadas->sum('corrigidos'),'acerto_pct'=>$validos?round((float)$agrupadas->sum('soma_pct')/$validos,1):null],
            'avaliacoes'=>$avaliacoes];
    }
}
