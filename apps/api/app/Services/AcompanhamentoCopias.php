<?php
namespace App\Services;
use App\Models\Usuario;
use App\Support\{ContextoInterno,ContextoVinculo,ElegibilidadeCopia,Espacos,Permissao,RegimeVinculos};
use Illuminate\Support\Facades\DB;

final class AcompanhamentoCopias
{
    public function listar(Usuario $ator,string $lado,?int $espaco,int $pagina=1): array
    {
        abort_unless(config('copias.interface_ativa')===true && config('copias.ativo')===true
            && Espacos::ativo() && RegimeVinculos::obrigatorio(),404,'Área indisponível.');
        abort_unless(!ContextoInterno::presente($ator) && ContextoVinculo::presente($ator),403,'Contexto indisponível.');
        $v=ContextoVinculo::resolver($ator);
        abort_unless(in_array($v->perfil,['admin_rede','dir_escolar','professor_autonomo'],true),403,'Acesso indisponível.');
        $lados=array_values(array_filter(['origem','destino'],fn($l)=>Permissao::pode($ator,'copia.autorizar_'.$l)));
        abort_unless(in_array($lado,$lados,true),403,'Acesso indisponível.');
        if ($v->perfil==='admin_rede') {
            $opcoes=Espacos::escolas()->where('ee.cliente_id',$v->espaco->cliente_id)->whereIn('e.id',ContextoVinculo::escolas($ator))
                ->orderBy('ee.nome')->orderBy('ee.id')->get(['ee.id','ee.nome'])->map(fn($e)=>['id'=>(int)$e->id,'nome'=>$e->nome])->all();
        } else $opcoes=[['id'=>(int)$v->espaco_id,'nome'=>$v->espaco->nome]];
        $espaco??=$opcoes[0]['id']??null;
        if ($espaco===null) return ['espacos'=>[],'espaco_id'=>null,'lado'=>$lado,'lados'=>$lados,'lista'=>['data'=>[],'total'=>0,'current_page'=>1,'last_page'=>1]];
        abort_unless(in_array($espaco,array_column($opcoes,'id'),true),404,'Espaço indisponível.');
        ElegibilidadeCopia::lado($ator,$espaco,$lado);
        $q=DB::table('copias_lotes')->where($lado.'_espaco_id',$espaco)->where('auditoria_versao',1)
            ->whereIn('politica',['t5-material-v1',LotesCadastroCopia::POLITICA,LotesResultadosCopia::POLITICA]);
        if ($lado==='destino') $q->whereExists(fn($s)=>$s->selectRaw('1')->from('copias_autorizacoes')
            ->whereColumn('copias_autorizacoes.lote_id','copias_lotes.id')->where('lado','destino'));
        $total=(clone $q)->count(); $pagina=max(1,min($pagina,max(1,(int)ceil($total/20))));
        $linhas=$q->orderByDesc('created_at')->orderByDesc('id')->offset(($pagina-1)*20)->limit(20)
            ->select(['id','finalidade','estado','total_itens','created_at'])
            ->selectSub(DB::table('copias_itens')->selectRaw('COUNT(*)')->whereColumn('lote_id','copias_lotes.id')->where('estado','publicado'),'publicados')
            ->selectSub(DB::table('copias_outbox')->selectRaw('COUNT(*)')->whereColumn('lote_id','copias_lotes.id')->whereNull('entregue_em'),'recibos_pendentes')->get();
        $dados=$linhas->map(fn($l)=>['id'=>$l->id,'finalidade'=>$l->finalidade,'estado'=>$l->estado,'selecionados'=>(int)$l->total_itens,
            'publicados'=>(int)$l->publicados,'auditoria_pendente'=>(int)$l->recibos_pendentes>0,'criado_em'=>$l->created_at])->all();
        return ['espacos'=>$opcoes,'espaco_id'=>$espaco,'lado'=>$lado,'lados'=>$lados,
            'lista'=>['data'=>$dados,'total'=>$total,'current_page'=>$pagina,'last_page'=>max(1,(int)ceil($total/20))]];
    }
}
