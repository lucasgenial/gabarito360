<?php
namespace App\Services;
use App\Models\Usuario;
use App\Support\{ElegibilidadeCopia,Espacos};
use Illuminate\Support\Facades\DB;

/** Consulta na origem: não preserva versão histórica nem cria autorização. */
final class SelecaoResultadosCopia
{
    private function consulta(int $espaco)
    {
        return DB::table('notas')->where('notas.espaco_id',$espaco)
            ->join('alunos',fn($j)=>$j->on('alunos.id','=','notas.aluno_id')->on('alunos.espaco_id','=','notas.espaco_id'))
            ->join('provas',fn($j)=>$j->on('provas.id','=','notas.prova_id')->on('provas.espaco_id','=','notas.espaco_id'))
            ->join('turmas',fn($j)=>$j->on('turmas.id','=','notas.turma_id')->on('turmas.espaco_id','=','notas.espaco_id'))
            ->join('cartoes',fn($j)=>$j->on('cartoes.id','=','notas.cartao_id')->on('cartoes.espaco_id','=','notas.espaco_id')
                ->on('cartoes.prova_id','=','notas.prova_id')->on('cartoes.aluno_id','=','notas.aluno_id'))
            ->whereRaw('COALESCE(cartoes.turma_aplicacao_id, alunos.turma_id) = notas.turma_id')
            ->whereIn('provas.id',Espacos::conferirEscola(DB::table('provas'),'provas')->where('espaco_id',$espaco)->select('provas.id'))
            ->whereIn('turmas.id',Espacos::conferirEscola(DB::table('turmas'),'turmas')->where('espaco_id',$espaco)->select('turmas.id'))
            ->whereExists(fn($q)=>$q->selectRaw('1')->from('prova_turmas')->whereColumn('prova_turmas.espaco_id','notas.espaco_id')
                ->whereColumn('prova_turmas.prova_id','notas.prova_id')->whereColumn('prova_turmas.turma_id','notas.turma_id'));
    }
    private function campos(): array
    { return ['notas.id','notas.nota_final','notas.prova_id','notas.aluno_id','notas.turma_id','provas.titulo as prova','alunos.nome as aluno','alunos.matricula','turmas.nome as turma']; }
    private function rotulo(object $r): array
    { return ['id'=>(int)$r->id,'prova'=>$r->prova,'aluno'=>$r->aluno,'matricula'=>$r->matricula,'turma'=>$r->turma,'nota_final'=>number_format((float)$r->nota_final,2,'.','')]; }
    public function catalogo(Usuario $ator,?int $espaco,int $pagina=1): array
    {
        $c=(new AcompanhamentoCopias)->listar($ator,'origem',$espaco); $espaco=$c['espaco_id']; $q=$this->consulta($espaco??0);
        $total=(clone $q)->count(); $ultima=max(1,(int)ceil($total/20)); $pagina=max(1,min($pagina,$ultima));
        $itens=$q->orderByDesc('notas.id')->offset(($pagina-1)*20)->limit(20)->get($this->campos())->map(fn($r)=>$this->rotulo($r))->all();
        if($espaco!==null) ElegibilidadeCopia::lado($ator,$espaco,'origem');
        return ['espacos'=>$c['espacos'],'espaco_id'=>$espaco,'lista'=>['data'=>$itens,'total'=>$total,'current_page'=>$pagina,'last_page'=>$ultima]];
    }
    public function previa(Usuario $ator,int $espaco,string $tipo,array $notas): array
    {
        (new AcompanhamentoCopias)->listar($ator,'origem',$espaco);
        $p=(new PreviaResultadosCopia)->previaOrigem($ator,$espaco,$tipo,$notas);
        $linhas=$this->consulta($espaco)->whereIn('notas.id',$notas)->orderBy('notas.id')->get($this->campos())->keyBy('id');
        abort_unless($linhas->count()===count($notas),409,'Dependências indisponíveis.');
        $deps=['provas'=>[],'turmas'=>[],'alunos'=>[]]; $itens=[];
        foreach($p['resultados'] as $r) {
            $l=$linhas[$r['nota_origem_id']];
            abort_unless((int)$l->prova_id===$r['prova_origem_id'] && (int)$l->aluno_id===$r['aluno_origem_id']
                && (int)$l->turma_id===$r['turma_origem_id'] && number_format((float)$l->nota_final,2,'.','')===$r['dados']['nota_final'],409,'Seleção mudou; confira novamente.');
            $deps['provas'][(int)$l->prova_id]=['id'=>(int)$l->prova_id,'titulo'=>$l->prova];
            $deps['turmas'][(int)$l->turma_id]=['id'=>(int)$l->turma_id,'nome'=>$l->turma];
            $deps['alunos'][(int)$l->aluno_id]=['id'=>(int)$l->aluno_id,'nome'=>$l->aluno,'matricula'=>$l->matricula];
            $rotulo=$this->rotulo($l); unset($rotulo['nota_final']);
            $itens[]=$rotulo+['dados'=>$r['dados'],'versao_historica_comprovada'=>false];
        }
        foreach($deps as &$grupo) { ksort($grupo); $grupo=array_values($grupo); } unset($grupo);
        $saida=['espaco_id'=>$espaco,'tipo_destino'=>$tipo,'resultados'=>$itens,'dependencias'=>$deps,
            'versao_historica_comprovada'=>false,'autoriza_execucao'=>false];
        abort_if(strlen(json_encode($saida,JSON_THROW_ON_ERROR))>524288,422,'Prévia excede o limite.');
        ElegibilidadeCopia::lado($ator,$espaco,'origem'); return $saida;
    }
}
