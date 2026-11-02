<?php
namespace App\Services;
use App\Models\Usuario;
use App\Support\{ElegibilidadeCopia,Espacos,MaterialCopia};
use Illuminate\Support\Facades\DB;

/** Adaptador de leitura para a interface; não cria lotes ou decisões. */
final class SelecaoMaterialCopia
{
    public function catalogo(Usuario $ator,?int $espaco,int $pagina=1): array
    {
        $c=(new AcompanhamentoCopias)->listar($ator,'origem',$espaco);
        $espaco=$c['espaco_id'];
        $q=Espacos::conferirEscola(DB::table('provas'),'provas')->where('espaco_id',$espaco??0);
        $total=(clone $q)->count(); $pagina=max(1,min($pagina,max(1,(int)ceil($total/20))));
        $itens=$q->orderByDesc('id')->offset(($pagina-1)*20)->limit(20)->get(['id','titulo','disciplina','serie','num_questoes'])
            ->map(fn($p)=>['id'=>(int)$p->id,'titulo'=>$p->titulo,'disciplina'=>$p->disciplina,'serie'=>$p->serie,'num_questoes'=>(int)$p->num_questoes])->all();
        return ['espacos'=>$c['espacos'],'espaco_id'=>$espaco,'lista'=>['data'=>$itens,'total'=>$total,'current_page'=>$pagina,'last_page'=>max(1,(int)ceil($total/20))]];
    }

    public function previa(Usuario $ator,int $espaco,array $provas): array
    {
        (new AcompanhamentoCopias)->listar($ator,'origem',$espaco);
        abort_unless(array_is_list($provas) && count($provas)>=1 && count($provas)<=20,422,'Seleção inválida.');
        foreach ($provas as $id) abort_unless(is_int($id) && $id>0,422,'Seleção inválida.');
        abort_unless(count(array_unique($provas))===count($provas),422,'Seleção inválida.'); sort($provas);
        $itens=[]; $bytes=0;
        foreach ($provas as $id) {
            $p=MaterialCopia::projetar($espaco,$id);
            $json=json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $bytes+=strlen($json); abort_if(strlen($json)>65536 || $bytes>524288,422,'Prévia excede o limite.');
            $itens[]=['origem_id'=>$id,'material'=>$p];
        }
        // Reconfere o contexto no fim da leitura; não substitui locks/decisões do lote futuro.
        ElegibilidadeCopia::lado($ator,$espaco,'origem');
        return ['espaco_id'=>$espaco,'materiais'=>$itens,'conteudo_digest'=>hash('sha256',json_encode([$espaco,$itens],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)),
            'revisao_obrigatoria'=>true,'autoriza_execucao'=>false];
    }
}
