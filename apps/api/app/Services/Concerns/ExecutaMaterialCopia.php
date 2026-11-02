<?php

namespace App\Services\Concerns;

use App\Models\Usuario;
use App\Services\OutboxCopia;
use App\Support\{ContextoVinculo,ContextoCorrecao,EventoCopia,MaterialCopia};
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\{DB,Crypt};
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Compartilha as transações/validações de LotesCopia; uma prova por unidade. */
trait ExecutaMaterialCopia
{
    public function solicitarExecucao(Usuario $ator, string $id): ?string
    {
        abort_unless(config('copias.execucao_ativa') === true,403,'Execução indisponível.');
        return $this->transacao(function () use ($ator,$id) {
            $lote=$this->lote($id); $contexto=$this->contexto($ator,$lote,'destino');
            $existente=DB::table('copias_execucoes')->where('lote_id',$id)->lockForUpdate()->first();
            if ($existente) {
                abort_unless((int)$existente->ator_id === $contexto['usuario_id'] && (int)$existente->vinculo_id === $contexto['vinculo_id'],403);
                return $id; // Repetir não reabre estado bloqueado nem altera autor da execução.
            }
            if (!$this->conferir($lote,$ator)) return null;
            abort_unless((new OutboxCopia)->confirmada($id),409,'Aguarde confirmação da auditoria.');
            $evento=EventoCopia::criar($lote,$ator,'execucao_solicitada',null);
            DB::table('copias_execucoes')->insert(['lote_id'=>$id,'ator_id'=>$contexto['usuario_id'],
                'vinculo_id'=>$contexto['vinculo_id'],'versao_acesso'=>$contexto['versao_acesso'],
                'contexto_evento'=>json_encode($evento,JSON_THROW_ON_ERROR),'estado'=>'pendente',
                'proxima_tentativa_em'=>now(),'created_at'=>now(),'updated_at'=>now()]);
            OutboxCopia::persistir($evento);
            DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'aguardando_execucao','updated_at'=>now()]);
            return $id;
        });
    }

    public function executarPasso(string $id): string
    {
        if (!config('copias.execucao_ativa') || !config('copias.ativo')) return 'pausado';
        return $this->transacao(function () use ($id) {
            $lote=$this->lote($id);
            $exec=DB::table('copias_execucoes')->where('lote_id',$id)->lockForUpdate()->first();
            if (!$exec) return 'ausente';
            if (in_array($exec->estado,['concluido','bloqueado'],true)) return $exec->estado;
            if ($lote->estado === 'bloqueado') return $this->bloquearExecucao($exec,'lote_bloqueado');
            DB::table('copias_execucoes')->where('lote_id',$id)->update(['proxima_tentativa_em'=>now()->addMinute(),'updated_at'=>now()]);
            if ($exec->estado === 'aguardando_recibo') {
                if (!(new OutboxCopia)->confirmada($id)) return 'aguardando_recibo';
                DB::table('copias_execucoes')->where('lote_id',$id)->update(['estado'=>'concluido','erro_tecnico'=>null,'updated_at'=>now()]);
                DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'concluido','updated_at'=>now()]);
                return 'concluido';
            }
            try {
                $usuario=Usuario::find($exec->ator_id); abort_unless($usuario,403);
                $ator=$usuario->noVinculo($exec->vinculo_id);
                $this->contexto($ator,$lote,'destino');
                abort_unless(hash_equals($exec->versao_acesso,ContextoVinculo::versaoAcesso($ator)),403);
                if (!$this->conferir($lote,$ator)) return $this->bloquearExecucao($exec,'autorizacao_invalida');
                if (!(new OutboxCopia)->confirmada($id)) return 'aguardando_auditoria';
                $item=DB::table('copias_itens')->where('lote_id',$id)->where('estado','<>','publicado')->orderBy('id')->lockForUpdate()->first();
                if (!$item) throw new \DomainException('Estado de unidade divergente.');
                $atual=MaterialCopia::projetar((int)$lote->origem_espaco_id,(int)$item->origem_id);
                $json=json_encode($atual,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
                if (strlen($json)>65536) throw new \DomainException('Unidade acima do limite.');
                $digest=hash('sha256',$json);
                $stage=DB::table('copias_staging')->where('item_id',$item->id)->lockForUpdate()->first();
                if ($item->estado === 'selecionado') {
                    if ($stage) throw new \DomainException('Staging divergente.');
                    DB::table('copias_staging')->insert(['item_id'=>$item->id,'conteudo_cifrado'=>Crypt::encryptString($json),
                        'digest'=>$digest,'created_at'=>now()]);
                    DB::table('copias_itens')->where('id',$item->id)->update(['estado'=>'preparado']);
                    DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'em_processamento','updated_at'=>now()]);
                    return 'preparado';
                }
                if (!$stage || !$stage->conteudo_cifrado || !hash_equals($digest,$stage->digest)) throw new \DomainException('Staging divergente.');
                $conteudo=Crypt::decryptString($stage->conteudo_cifrado);
                if (!hash_equals($stage->digest,hash('sha256',$conteudo))) throw new \DomainException('Staging divergente.');
                $material=json_decode($conteudo,true,32,JSON_THROW_ON_ERROR);
                $destino=DB::table('espacos')->find($lote->destino_espaco_id);
                $meta=ContextoCorrecao::autoria((int)$destino->id,(int)$ator->id);
                $prova=DB::table('provas')->insertGetId($material['prova'] + $meta + [
                    'escola_id'=>$destino->escola_id,'espaco_tipo'=>$destino->tipo,'status'=>'rascunho',
                    'gerar_cartao_pdf'=>false,'data_aplicacao'=>null,'correcao_fechada_em'=>null,'correcao_fechada_por'=>null,
                    'created_at'=>now(),'updated_at'=>now()]);
                $gabarito=DB::table('gabaritos')->insertGetId($meta + ['prova_id'=>$prova,
                    'publicado_por'=>null,'publicado_em'=>null,'created_at'=>now(),'updated_at'=>now()]);
                foreach ($material['questoes'] as $questao) DB::table('gabarito_questoes')->insert($questao + $meta + ['gabarito_id'=>$gabarito]);
                DB::table('registros_origem')->insert(['lote_id'=>$id,'entidade'=>'prova',
                    'origem_espaco_id'=>$lote->origem_espaco_id,'origem_id'=>$item->origem_id,
                    'destino_espaco_id'=>$destino->id,'destino_id'=>$prova,'copiado_por'=>$ator->id,'created_at'=>now()]);
                DB::table('copias_itens')->where('id',$item->id)->update(['estado'=>'publicado']);
                DB::table('copias_staging')->where('item_id',$item->id)->update(['conteudo_cifrado'=>null]);
                $this->eventoExecucao($exec,'item_publicado',(int)$item->id);
                if (!DB::table('copias_itens')->where('lote_id',$id)->where('estado','<>','publicado')->exists()) {
                    DB::table('copias_execucoes')->where('lote_id',$id)->update(['estado'=>'aguardando_recibo','erro_tecnico'=>null,'updated_at'=>now()]);
                    DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'aguardando_recibo','updated_at'=>now()]);
                    $this->eventoExecucao($exec,'publicacao_concluida');
                }
                return 'publicado';
            } catch (HttpException $e) {
                if (!in_array($e->getStatusCode(),[403,404,409,422],true)) throw $e;
                return $this->bloquearExecucao($exec,'autorizacao_ou_fonte_invalida');
            } catch (DecryptException|\JsonException|\DomainException) {
                return $this->bloquearExecucao($exec,'integridade_invalida');
            }
        });
    }

    public function registrarFalhaExecucao(string $id): void
    {
        DB::transaction(function () use ($id) {
            $exec=DB::table('copias_execucoes')->where('lote_id',$id)->lockForUpdate()->first();
            if (!$exec || in_array($exec->estado,['concluido','bloqueado'],true)) return;
            DB::table('copias_execucoes')->where('lote_id',$id)->increment('falhas',1,
                ['erro_tecnico'=>'execucao_falhou','proxima_tentativa_em'=>now()->addMinute(),'updated_at'=>now()]);
            $this->eventoExecucao($exec,'execucao_falhou');
        });
    }

    private function bloquearExecucao(object $exec, string $codigo): string
    {
        DB::table('copias_execucoes')->where('lote_id',$exec->lote_id)->update(['estado'=>'bloqueado','erro_tecnico'=>$codigo,'updated_at'=>now()]);
        DB::table('copias_lotes')->where('id',$exec->lote_id)->update(['estado'=>'bloqueado','updated_at'=>now()]);
        $this->eventoExecucao($exec,'bloqueado');
        return 'bloqueado';
    }

    private function eventoExecucao(object $exec, string $acao, ?int $item = null): void
    {
        $base=json_decode($exec->contexto_evento,true,32,JSON_THROW_ON_ERROR);
        OutboxCopia::persistir(EventoCopia::sistema($base,$acao,$item));
    }
}
