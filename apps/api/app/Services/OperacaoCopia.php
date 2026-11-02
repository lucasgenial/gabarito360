<?php
namespace App\Services;
use App\Models\Usuario;
use App\Support\ElegibilidadeCopia;
use Illuminate\Support\Facades\DB;

/** Adaptador de interface; os serviços mantêm suas próprias transações e revalidações. */
final class OperacaoCopia
{
    private const POLITICAS=['preparacao_material'=>'t5-material-v1','cadastro_minimo'=>LotesCadastroCopia::POLITICA,'resultados_identificados'=>LotesResultadosCopia::POLITICA];
    private function lote(Usuario $ator,string $id,string $lado): object
    {
        RecebimentosCopia::exigirInterface(); abort_unless(in_array($lado,['origem','destino'],true),422);
        $l=DB::table('copias_lotes')->where('id',$id)->where('auditoria_versao',1)->first();
        abort_unless($l && (self::POLITICAS[$l->finalidade]??null)===$l->politica,404);
        ElegibilidadeCopia::lado($ator,(int)$l->{$lado.'_espaco_id'},$lado);
        abort_unless(DB::table('copias_recebimentos')->where('lote_id',$id)->where('finalidade',$l->finalidade)->exists(),404);
        if($lado==='destino') abort_unless(DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado','destino')->exists(),404);
        return $l;
    }
    private function servico(object $l): LotesCopia|LotesCadastroCopia|LotesResultadosCopia
    {
        return match($l->finalidade) {
            'preparacao_material'=>new LotesCopia,
            'cadastro_minimo'=>new LotesCadastroCopia,
            'resultados_identificados'=>new LotesResultadosCopia,
        };
    }
    private function habilitada(object $l): bool
    {
        return config('copias.execucao_ativa')===true && match($l->finalidade) {
            'cadastro_minimo'=>config('copias.cadastro_execucao_ativa')===true,
            'resultados_identificados'=>config('copias.resultados_execucao_ativa')===true,
            default=>true,
        };
    }
    public function consultar(Usuario $ator,string $id,string $lado): array
    {
        $l=$this->lote($ator,$id,$lado);
        $publicados=DB::table('copias_itens')->where('lote_id',$id)->where('estado','publicado')->count();
        $exec=DB::table('copias_execucoes')->where('lote_id',$id)->exists();
        $pendente=DB::table('copias_outbox')->where('lote_id',$id)->whereNull('entregue_em')->exists();
        return ['lote_id'=>$id,'lado'=>$lado,'finalidade'=>$l->finalidade,'estado'=>$l->estado,'selecionados'=>(int)$l->total_itens,
            'publicados'=>$publicados,'auditoria_pendente'=>$pendente,'execucao_solicitada'=>$exec,
            'pode_solicitar'=>$lado==='destino' && $this->habilitada($l) && !$pendente
                && in_array($l->estado,['aguardando_auditoria','aguardando_execucao','em_processamento'],true),
            'pode_revogar'=>!in_array($l->estado,['bloqueado','concluido','aguardando_recibo'],true) && $publicados<(int)$l->total_itens,
            'acervo_disponivel'=>$lado==='destino' && $l->finalidade==='resultados_identificados'
                && DB::table('acervo_resultados')->where('lote_id',$id)->where('destino_espaco_id',$l->destino_espaco_id)->exists(),
            'autoriza_execucao'=>false];
    }
    public function solicitar(Usuario $ator,string $id): void
    {
        $l=$this->lote($ator,$id,'destino');
        abort_if($l->estado==='bloqueado',409,'Prepare nova solicitação.');
        // O contrato original decide prontidão e idempotência dentro de transação própria.
        $resultado=$this->servico($l)->solicitarExecucao($ator,$id);
        abort_if($resultado===null,409,'Solicitação indisponível.');
    }
    public function revogar(Usuario $ator,string $id,string $lado): void
    {
        $l=$this->lote($ator,$id,$lado);
        abort_if(in_array($l->estado,['aguardando_recibo','concluido'],true),409,'Publicação já realizada.');
        $this->servico($l)->revogar($ator,$id,$lado);
    }
    public function acervo(Usuario $ator,string $id): array
    {
        $l=$this->lote($ator,$id,'destino'); abort_unless($l->finalidade==='resultados_identificados',404);
        $aid=DB::table('acervo_resultados')->where('lote_id',$id)->where('destino_espaco_id',$l->destino_espaco_id)->value('id');
        abort_unless($aid,404);
        $a=(new AcervoResultados)->consultar($ator,(int)$l->destino_espaco_id,$aid);
        return ['lote_id'=>$id,'publicado_em'=>$a['publicado_em'],'pacote'=>PacoteResultadosInterface::apresentar($a['pacote'])];
    }
}
