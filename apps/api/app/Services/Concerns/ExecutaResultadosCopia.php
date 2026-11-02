<?php
namespace App\Services\Concerns;
use App\Models\Usuario;
use App\Services\OutboxCopia;
use App\Support\{ContextoVinculo,EventoCopia};
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\{Crypt,DB};
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ExecutaResultadosCopia
{
    public function solicitarExecucao(Usuario $ator,string $id): string
    {
        abort_unless($this->execucaoHabilitada(),403,'Execução indisponível.');
        return $this->transacao(function() use($ator,$id) {
            $l=$this->lote($id,false); $c=$this->contexto($ator,$l,'destino');
            abort_if($l->estado==='bloqueado',409,'Lote bloqueado.');
            $e=DB::table('copias_execucoes')->where('lote_id',$id)->lockForUpdate()->first();
            if ($e) { abort_unless((int)$e->ator_id===$c['usuario_id'] && (int)$e->vinculo_id===$c['vinculo_id'],403); return $id; }
            $this->validarResultados($l);
            abort_unless((new OutboxCopia)->confirmada($id),409,'Auditoria pendente.');
            $evento=EventoCopia::criar($l,$ator,'execucao_solicitada',null);
            DB::table('copias_execucoes')->insert(['lote_id'=>$id,'ator_id'=>$c['usuario_id'],'vinculo_id'=>$c['vinculo_id'],
                'versao_acesso'=>$c['versao_acesso'],'contexto_evento'=>$this->json($evento),'estado'=>'pendente',
                'proxima_tentativa_em'=>now(),'created_at'=>now(),'updated_at'=>now()]);
            OutboxCopia::persistir($evento);
            DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'aguardando_execucao','updated_at'=>now()]); return $id;
        });
    }

    public function executarPasso(string $id): string
    {
        if (!$this->execucaoHabilitada()) return 'pausado';
        return $this->transacao(function() use($id) {
            $l=$this->lote($id,false); $e=DB::table('copias_execucoes')->where('lote_id',$id)->lockForUpdate()->first();
            if (!$e) return 'ausente';
            if (in_array($e->estado,['concluido','bloqueado'],true)) return $e->estado;
            if ($l->estado==='bloqueado') return $this->bloquearResultados($e,'lote_bloqueado');
            DB::table('copias_execucoes')->where('lote_id',$id)->update(['proxima_tentativa_em'=>now()->addMinute(),'updated_at'=>now()]);
            if ($e->estado==='aguardando_recibo') {
                if (!(new OutboxCopia)->confirmada($id)) return 'aguardando_recibo';
                DB::table('copias_execucoes')->where('lote_id',$id)->update(['estado'=>'concluido','erro_tecnico'=>null,'updated_at'=>now()]);
                DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'concluido','updated_at'=>now()]); return 'concluido';
            }
            try {
                $u=Usuario::find($e->ator_id); abort_unless($u,403);
                $ator=$u->noVinculo($e->vinculo_id); $this->contexto($ator,$l,'destino');
                abort_unless(hash_equals($e->versao_acesso,ContextoVinculo::versaoAcesso($ator)),403);
                $p=$this->validarResultados($l);
                if (!(new OutboxCopia)->confirmada($id)) return 'aguardando_auditoria';
                $itens=DB::table('copias_itens')->where('lote_id',$id)->orderBy('origem_id')->lockForUpdate()->get();
                $item=$itens->first(); if (!$item) throw new \DomainException('Unidade ausente.');
                $json=$this->json($p); if (strlen($json)>524288) throw new \DomainException('Limite excedido.');
                $digest=hash('sha256',$json);
                $stage=DB::table('copias_staging')->where('item_id',$item->id)->lockForUpdate()->first();
                if ($itens->every(fn($i)=>$i->estado==='selecionado')) {
                    if ($stage) throw new \DomainException('Staging divergente.');
                    DB::table('copias_staging')->insert(['item_id'=>$item->id,'conteudo_cifrado'=>Crypt::encryptString($json),'digest'=>$digest,'created_at'=>now()]);
                    DB::table('copias_itens')->where('lote_id',$id)->update(['estado'=>'preparado']);
                    DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'em_processamento','updated_at'=>now()]); return 'preparado';
                }
                if (!$itens->every(fn($i)=>$i->estado==='preparado') || !$stage || !$stage->conteudo_cifrado
                    || !hash_equals($digest,$stage->digest) || !hash_equals($digest,hash('sha256',Crypt::decryptString($stage->conteudo_cifrado)))) throw new \DomainException('Staging divergente.');
                return DB::transaction(function() use($l,$e,$itens,$item,$json,$digest) {
                    $acervo=(string)Str::uuid();
                    DB::table('acervo_resultados')->insert(['id'=>$acervo,'lote_id'=>$l->id,'origem_espaco_id'=>$l->origem_espaco_id,
                        'destino_espaco_id'=>$l->destino_espaco_id,'conteudo_cifrado'=>Crypt::encryptString($json),'conteudo_digest'=>$digest,
                        'copiado_por'=>$e->ator_id,'created_at'=>now()->utc()]);
                    foreach ($itens as $i) {
                        $novo=DB::table('acervo_resultados_itens')->insertGetId(['acervo_id'=>$acervo,'item_id'=>$i->id]);
                        DB::table('registros_origem')->insert(['lote_id'=>$l->id,'entidade'=>'nota','origem_id'=>$i->origem_id,
                            'origem_espaco_id'=>$l->origem_espaco_id,'destino_espaco_id'=>$l->destino_espaco_id,'destino_id'=>$novo,'copiado_por'=>$e->ator_id,'created_at'=>now()]);
                        $this->eventoResultados($e,'item_publicado',(int)$i->id);
                    }
                    DB::table('copias_itens')->where('lote_id',$l->id)->update(['estado'=>'publicado']);
                    DB::table('copias_staging')->where('item_id',$item->id)->update(['conteudo_cifrado'=>null]);
                    DB::table('copias_execucoes')->where('lote_id',$l->id)->update(['estado'=>'aguardando_recibo','erro_tecnico'=>null,'updated_at'=>now()]);
                    DB::table('copias_lotes')->where('id',$l->id)->update(['estado'=>'aguardando_recibo','updated_at'=>now()]);
                    $this->eventoResultados($e,'publicacao_concluida'); return 'publicado';
                });
            } catch (HttpException $x) {
                if (!in_array($x->getStatusCode(),[403,404,409,422],true)) throw $x;
                return $this->bloquearResultados($e,'autorizacao_ou_conflito');
            } catch (DecryptException|\JsonException|\DomainException) { return $this->bloquearResultados($e,'integridade_invalida'); }
        });
    }

    private function validarResultados(object $l): array
    {
        abort_unless($this->evidenciaValida($l) && $this->decisaoValida($l,'origem') && $this->decisaoValida($l,'destino'),409,'Autorização indisponível.');
        $p=$this->fonte($l);
        abort_if(DB::table('registros_origem')->where('entidade','nota')->where('origem_espaco_id',$l->origem_espaco_id)
            ->where('destino_espaco_id',$l->destino_espaco_id)->whereIn('origem_id',array_column($p['resultados'],'nota_origem_id'))->exists(),409,'Resultado já transferido.');
        return $p;
    }
    public function registrarFalhaExecucao(string $id): void
    {
        DB::transaction(function() use($id) {
            $this->lote($id,false); $e=DB::table('copias_execucoes')->where('lote_id',$id)->lockForUpdate()->first();
            if (!$e || in_array($e->estado,['concluido','bloqueado'],true)) return;
            DB::table('copias_execucoes')->where('lote_id',$id)->increment('falhas',1,['erro_tecnico'=>'execucao_falhou','proxima_tentativa_em'=>now()->addMinute(),'updated_at'=>now()]);
            $this->eventoResultados($e,'execucao_falhou');
        });
    }
    private function bloquearResultados(object $e,string $codigo): string
    {
        DB::table('copias_execucoes')->where('lote_id',$e->lote_id)->update(['estado'=>'bloqueado','erro_tecnico'=>$codigo,'updated_at'=>now()]);
        DB::table('copias_lotes')->where('id',$e->lote_id)->update(['estado'=>'bloqueado','updated_at'=>now()]);
        $this->eventoResultados($e,'bloqueado'); return 'bloqueado';
    }
    private function eventoResultados(object $e,string $acao,?int $item=null): void
    { OutboxCopia::persistir(EventoCopia::sistema(json_decode($e->contexto_evento,true,32,JSON_THROW_ON_ERROR),$acao,$item)); }
    private function execucaoHabilitada(): bool
    { return config('copias.ativo')===true && config('copias.execucao_ativa')===true && config('copias.resultados_execucao_ativa')===true; }
}
