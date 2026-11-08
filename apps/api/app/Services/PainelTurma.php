<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{Escopo,ConsultasPedagogicas,Permissao,Cabecalho,PerfisEscolares,Espacos};
use Illuminate\Support\Facades\DB;

/** DTO único para tela e documento; autorização antecede opções, filtros e agregados. */
final class PainelTurma
{
    public function montar(Usuario $u,int $id,array $filtros): array
    {
        abort_unless(Permissao::pode($u,'relatorio.turma')&&!PerfisEscolares::administrativo($u->perfil),403);
        $turma=Escopo::turmas($u)->findOrFail($id);
        $consulta=new ConsultasPedagogicas($u,historico:true);
        $base=Escopo::provasHistoricas($u)->whereIn('provas.id',$consulta->tabela('prova_turmas')->where('turma_id',$id)->select('prova_id'))
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
        $provas=$base->orderBy('provas.data_aplicacao')->orderBy('provas.id')->get(['provas.id','provas.espaco_id','provas.opcao_lingua','provas.titulo','provas.disciplina','provas.data_aplicacao','provas.nota_maxima']);
        // Uma nota antiga não torna concluído um cartão vigente que voltou à revisão.
        // O join é 1:1 pela PK do cartão, sem multiplicar resultados por áreas/disciplinas.
        $notas=$consulta->tabela('notas')->where('notas.turma_id',$id)->whereIn('notas.prova_id',$provas->pluck('id'))
            ->join('cartoes as painel_resultado','painel_resultado.id','=','notas.cartao_id')->where('painel_resultado.status','lido');
        $percentualValido='notas.total_questoes > 0 AND notas.acertos >= 0 AND notas.acertos <= notas.total_questoes';
        $agrupadas=(clone $notas)->select('notas.prova_id')->selectRaw('COUNT(*) as corrigidos, AVG(notas.nota_final) as media, '
            ."SUM(CASE WHEN $percentualValido THEN 100.0 * notas.acertos / notas.total_questoes ELSE 0 END) as soma_pct, "
            ."SUM(CASE WHEN $percentualValido THEN 1 ELSE 0 END) as validos, "
            ."SUM(CASE WHEN notas.status_aprovacao = 'aprovado' THEN 1 ELSE 0 END) as dentro_criterio, "
            ."SUM(CASE WHEN notas.status_aprovacao = 'recuperacao' THEN 1 ELSE 0 END) as indicacao_recuperacao")
            ->groupBy('notas.prova_id')->get()->keyBy('prova_id');
        $alunosComResultado=(clone $notas)->distinct()->count('notas.aluno_id');
        $podeAcompanhar=Permissao::pode($u,'correcao.acompanhar');
        $operacoes=$podeAcompanhar?$this->operacoes($consulta,$id,$provas->pluck('id')->all()):collect();
        $avaliacoes=$provas->map(function($p)use($agrupadas,$operacoes,$podeAcompanhar){
            $n=$agrupadas->get($p->id);$validos=(int)($n?->validos??0);
            $o=$operacoes->get($p->id);
            return ['titulo'=>$p->titulo,'disciplina'=>$p->disciplina,'data_aplicacao'=>$p->data_aplicacao,'corrigidos'=>(int)($n?->corrigidos??0),'nota_maxima'=>(float)$p->nota_maxima,
                'media'=>$n?->media!==null?round((float)$n->media,1):null,'acerto_pct'=>$validos?round((float)$n->soma_pct/$validos,1):null,
                'resultados_com_percentual'=>$validos,'situacoes'=>$this->situacoes((int)($n?->corrigidos??0),(int)($n?->dentro_criterio??0),(int)($n?->indicacao_recuperacao??0)),
                'pendencias_revisao'=>$podeAcompanhar?(int)($o?->revisao??0):null,'processamento'=>$podeAcompanhar?(int)($o?->processamento??0):null,
                'falhas'=>$podeAcompanhar?(int)($o?->falhas??0):null,'nao_identificados'=>$podeAcompanhar?(int)($o?->nao_identificados??0):null];
        })->values()->all();
        $validos=(int)$agrupadas->sum('validos');
        $total=(int)$agrupadas->sum('corrigidos');
        $acompanhamento=$podeAcompanhar?[
            'unidade'=>'cartoes_vigentes_com_turma_atribuida','total'=>(int)$operacoes->sum('total'),
            'revisao'=>(int)$operacoes->sum('revisao'),'processamento'=>(int)$operacoes->sum('processamento'),
            'falhas'=>(int)$operacoes->sum('falhas'),'nao_identificados'=>(int)$operacoes->sum('nao_identificados'),
            'aguardando_leitura'=>(int)$operacoes->sum('aguardando_leitura'),'base_esperada_confiavel'=>false,
        ]:null;
        return ['turma'=>['id'=>$turma->id,'nome'=>$turma->nome,'serie'=>$turma->serie,'ano_letivo'=>$turma->ano_letivo,'escola_id'=>$turma->escola_id],
            'cabecalho'=>Cabecalho::contexto($u,$turma->escola_id),'filtros'=>$filtros,
            'recorte'=>['disciplina'=>!empty($filtros['disciplina_id'])?$disciplinas->firstWhere('id',(int)$filtros['disciplina_id'])->nome:'Todas as disciplinas',
                'professor'=>!empty($filtros['professor_id'])?$autores->firstWhere('id',(int)$filtros['professor_id'])->nome:'Todos os autores autorizados'],
            'opcoes'=>['disciplinas'=>$disciplinas->all(),'professores'=>$autores->all()],
            'kpis'=>['alunos'=>$consulta->tabela('alunos')->where('turma_id',$id)->where('ativo',true)->count(),'avaliacoes'=>$provas->count(),'resultados'=>$total,'acerto_pct'=>$validos?round((float)$agrupadas->sum('soma_pct')/$validos,1):null,
                'alunos_com_resultado'=>$alunosComResultado,'avaliacoes_sem_resultado'=>collect($avaliacoes)->where('corrigidos',0)->count(),
                'avaliacoes_sem_data'=>$provas->whereNull('data_aplicacao')->count(),'resultados_com_percentual'=>$validos,
                'leituras_revisao'=>$acompanhamento['revisao']??null],
            'situacoes_resultados'=>$this->situacoes($total,(int)$agrupadas->sum('dentro_criterio'),(int)$agrupadas->sum('indicacao_recuperacao')),
            'acompanhamento'=>$acompanhamento,
            'analise'=>(new AnalisePainelTurma)->montar($u,$consulta,$notas,$provas,$total,$filtros['analise_prova_id']??null,$agrupadas),
            'avaliacoes'=>$avaliacoes];
    }

    private function situacoes(int $total,int $criterio,int $recuperacao): array
    {
        return ['total'=>$total,'dentro_criterio'=>$criterio,'indicacao_recuperacao'=>$recuperacao,'outros'=>$total-$criterio-$recuperacao];
    }

    private function operacoes(ConsultasPedagogicas $consulta,int $turma,array $provas)
    {
        $cartoes=$consulta->tabela('cartoes')->whereIn('cartoes.prova_id',$provas)
            ->leftJoin('alunos as painel_aluno','painel_aluno.id','=','cartoes.aluno_id')
            ->whereRaw('COALESCE(cartoes.turma_aplicacao_id, painel_aluno.turma_id) = ?',[$turma])
            ->select('cartoes.prova_id','cartoes.status','cartoes.aluno_id')
            ->selectSub(function($envio){
                $envio->from('cartao_envios')->select('status')->whereColumn('cartao_id','cartoes.id')->whereColumn('prova_id','cartoes.prova_id');
                if(Espacos::ativo())$envio->whereColumn('espaco_id','cartoes.espaco_id');
                $envio->orderByDesc('id')->limit(1);
            },'envio_status');
        // A consulta derivada projeta um único estado mais recente por cartão vigente.
        // Não soma tentativas arquivadas nem atribui à turma uma foto sem turma/aluno.
        return DB::query()->fromSub($cartoes,'painel_cartoes')->select('prova_id')->selectRaw('COUNT(*) as total, '
            ."SUM(CASE WHEN status = 'ambiguo' THEN 1 ELSE 0 END) as revisao, "
            ."SUM(CASE WHEN status = 'pendente' AND envio_status IN ('na_fila','processando') THEN 1 ELSE 0 END) as processamento, "
            ."SUM(CASE WHEN status = 'pendente' AND envio_status IN ('erro','recusado') THEN 1 ELSE 0 END) as falhas, "
            ."SUM(CASE WHEN aluno_id IS NULL THEN 1 ELSE 0 END) as nao_identificados, "
            ."SUM(CASE WHEN status = 'pendente' AND (envio_status IS NULL OR envio_status NOT IN ('na_fila','processando','erro','recusado')) THEN 1 ELSE 0 END) as aguardando_leitura")
            ->groupBy('prova_id')->get()->keyBy('prova_id');
    }
}
