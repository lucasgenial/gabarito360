<?php
namespace App\Services;
use App\Models\Usuario;
use App\Support\{ElegibilidadeCopia,Espacos};
use Illuminate\Support\Facades\DB;

/** Leitura exclusiva da origem, sem criação ou autorização de lote. */
final class SelecaoCadastroCopia
{
    public function catalogo(Usuario $ator,?int $espaco,?int $turma=null,int $pagina=1,int $paginaAlunos=1): array
    {
        $c=(new AcompanhamentoCopias)->listar($ator,'origem',$espaco); $espaco=$c['espaco_id'];
        $q=Espacos::conferirEscola(DB::table('turmas'),'turmas')->where('espaco_id',$espaco??0);
        $selecionada=null;
        if ($turma!==null) {
            $selecionada=(clone $q)->where('id',$turma)->first(['id','nome','serie','ano_letivo']);
            abort_unless($selecionada,404,'Turma indisponível.');
        }
        $turmas=$this->pagina($q,$pagina,['id','nome','serie','ano_letivo']);
        $alunos=$this->pagina(DB::table('alunos')->where('espaco_id',$espaco??0)->where('turma_id',$selecionada?->id??0),$paginaAlunos,['id','nome','matricula']);
        if ($espaco!==null) ElegibilidadeCopia::lado($ator,$espaco,'origem');
        return ['espacos'=>$c['espacos'],'espaco_id'=>$espaco,'turmas'=>$turmas,'turma'=>$selecionada?(array)$selecionada:null,'alunos'=>$alunos];
    }
    public function previa(Usuario $ator,int $espaco,string $tipo,array $turmas,array $alunos): array
    {
        (new AcompanhamentoCopias)->listar($ator,'origem',$espaco);
        abort_unless(array_is_list($turmas) && count($turmas)===1 && array_is_list($alunos) && count($alunos)<=20,422,'Confirme uma turma e até 20 alunos.');
        $p=(new PreviaCadastroCopia)->previaOrigem($ator,$espaco,$tipo,$turmas,$alunos);
        unset($p['contexto_origem']);
        abort_if(strlen(json_encode($p,JSON_THROW_ON_ERROR))>524288,422,'Prévia excede o limite.');
        ElegibilidadeCopia::lado($ator,$espaco,'origem'); return $p;
    }
    private function pagina($q,int $pagina,array $campos): array
    {
        $total=(clone $q)->count(); $ultima=max(1,(int)ceil($total/20)); $pagina=max(1,min($pagina,$ultima));
        $itens=$q->orderBy('id')->offset(($pagina-1)*20)->limit(20)->get($campos)->map(function($i) { $i->id=(int)$i->id; return (array)$i; })->all();
        return ['data'=>$itens,'total'=>$total,'current_page'=>$pagina,'last_page'=>$ultima];
    }
}
