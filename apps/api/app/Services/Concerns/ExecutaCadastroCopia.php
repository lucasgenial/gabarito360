<?php
namespace App\Services\Concerns;

use App\Models\Usuario;
use App\Services\OutboxCopia;
use App\Support\{ContextoCorrecao,ContextoVinculo,EventoCopia};
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\{Crypt,DB};
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ExecutaCadastroCopia
{
    public function solicitarExecucao(Usuario $ator,string $id): string
    {
        abort_unless($this->execucaoHabilitada(),403,'Execução indisponível.');
        return $this->transacao(function() use($ator,$id) {
            $l=$this->lote($id); $c=$this->contexto($ator,$l,'destino');
            $e=DB::table('copias_execucoes')->where('lote_id',$id)->lockForUpdate()->first();
            if ($e) {
                abort_unless((int)$e->ator_id===$c['usuario_id'] && (int)$e->vinculo_id===$c['vinculo_id'],403);
                return $id;
            }
            $this->validarExecucaoCadastro($l);
            abort_unless((new OutboxCopia)->confirmada($id),409,'Auditoria pendente.');
            $evento=EventoCopia::criar($l,$ator,'execucao_solicitada',null);
            DB::table('copias_execucoes')->insert(['lote_id'=>$id,'ator_id'=>$c['usuario_id'],'vinculo_id'=>$c['vinculo_id'],
                'versao_acesso'=>$c['versao_acesso'],'contexto_evento'=>json_encode($evento,JSON_THROW_ON_ERROR),
                'estado'=>'pendente','proxima_tentativa_em'=>now(),'created_at'=>now(),'updated_at'=>now()]);
            OutboxCopia::persistir($evento);
            DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'aguardando_execucao','updated_at'=>now()]);
            return $id;
        });
    }

    public function executarPasso(string $id): string
    {
        if (!$this->execucaoHabilitada()) return 'pausado';
        return $this->transacao(function() use($id) {
            $l=$this->lote($id,false);
            $e=DB::table('copias_execucoes')->where('lote_id',$id)->lockForUpdate()->first();
            if (!$e) return 'ausente';
            if (in_array($e->estado,['concluido','bloqueado'],true)) return $e->estado;
            if ($l->estado==='bloqueado') return $this->bloquearCadastro($e,'lote_bloqueado');
            DB::table('copias_execucoes')->where('lote_id',$id)->update(['proxima_tentativa_em'=>now()->addMinute(),'updated_at'=>now()]);
            if ($e->estado==='aguardando_recibo') {
                if (!(new OutboxCopia)->confirmada($id)) return 'aguardando_recibo';
                DB::table('copias_execucoes')->where('lote_id',$id)->update(['estado'=>'concluido','erro_tecnico'=>null,'updated_at'=>now()]);
                DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'concluido','updated_at'=>now()]);
                return 'concluido'; // Só encerra recibos: não publica novos dados nem reabre autorização.
            }
            try {
                $u=Usuario::find($e->ator_id); abort_unless($u,403);
                $ator=$u->noVinculo($e->vinculo_id); $this->contexto($ator,$l,'destino');
                abort_unless(hash_equals($e->versao_acesso,ContextoVinculo::versaoAcesso($ator)),403);
                $p=$this->validarExecucaoCadastro($l);
                if (!(new OutboxCopia)->confirmada($id)) return 'aguardando_auditoria';
                $item=DB::table('copias_itens')->where('lote_id',$id)->where('entidade','turma')->where('estado','<>','publicado')->orderBy('origem_id')->lockForUpdate()->first();
                if (!$item) throw new \DomainException('Unidade divergente.');
                $turma=collect($p['turmas'])->firstWhere('origem_id',(int)$item->origem_id);
                $alunos=array_values(array_filter($p['alunos'],fn($a)=>$a['turma_origem_id']===(int)$item->origem_id));
                if (!$turma) throw new \DomainException('Unidade divergente.');
                $unidade=['turma'=>$turma,'alunos'=>$alunos];
                $json=json_encode($unidade,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
                if (strlen($json)>131072) throw new \DomainException('Unidade acima do limite.');
                $digest=hash('sha256',$json);
                $stage=DB::table('copias_staging')->where('item_id',$item->id)->lockForUpdate()->first();
                if ($item->estado==='selecionado') {
                    if ($stage) throw new \DomainException('Staging divergente.');
                    DB::table('copias_staging')->insert(['item_id'=>$item->id,'conteudo_cifrado'=>Crypt::encryptString($json),'digest'=>$digest,'created_at'=>now()]);
                    DB::table('copias_itens')->where('id',$item->id)->update(['estado'=>'preparado']);
                    DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'em_processamento','updated_at'=>now()]);
                    return 'preparado';
                }
                if (!$stage || !$stage->conteudo_cifrado || !hash_equals($digest,$stage->digest)) throw new \DomainException('Staging divergente.');
                $texto=Crypt::decryptString($stage->conteudo_cifrado);
                if (!hash_equals($digest,hash('sha256',$texto))) throw new \DomainException('Staging divergente.');
                $unidade=json_decode($texto,true,32,JSON_THROW_ON_ERROR);
                // Savepoint garante rollback mesmo se uma exceção de domínio surgir após inserções.
                return DB::transaction(function() use($l,$e,$item,$unidade,$ator) {
                    $destino=DB::table('espacos')->find($l->destino_espaco_id);
                    $meta=ContextoCorrecao::autoria((int)$destino->id,(int)$ator->id)+['espaco_tipo'=>$destino->tipo];
                    $turmaDestino=$this->publicarEntidadeCadastro($l,$e,'turma',$unidade['turma'],
                        $meta+['escola_id'=>$destino->escola_id]);
                    foreach ($unidade['alunos'] as $a) $this->publicarEntidadeCadastro($l,$e,'aluno',$a,$meta+['turma_id'=>$turmaDestino]);
                    DB::table('copias_staging')->where('item_id',$item->id)->update(['conteudo_cifrado'=>null]);
                    if (!DB::table('copias_itens')->where('lote_id',$l->id)->where('estado','<>','publicado')->exists()) {
                        DB::table('copias_execucoes')->where('lote_id',$l->id)->update(['estado'=>'aguardando_recibo','erro_tecnico'=>null,'updated_at'=>now()]);
                        DB::table('copias_lotes')->where('id',$l->id)->update(['estado'=>'aguardando_recibo','updated_at'=>now()]);
                        $this->eventoCadastro($e,'publicacao_concluida');
                    }
                    return 'publicado';
                });
            } catch (HttpException $x) {
                if (!in_array($x->getStatusCode(),[403,404,409,422],true)) throw $x;
                return $this->bloquearCadastro($e,'autorizacao_ou_conflito');
            } catch (DecryptException|\JsonException|\DomainException) {
                return $this->bloquearCadastro($e,'integridade_invalida');
            }
        });
    }

    private function validarExecucaoCadastro(object $l): array
    {
        abort_unless($this->evidenciaValida($l) && $this->decisaoValida($l,'origem') && $this->decisaoValida($l,'destino'),409,'Autorização indisponível.');
        $p=$this->fonte($l); $r=$this->conflitos($l,$p);
        abort_unless($r['sem_bloqueios'] && $r['digest']===DB::table('copias_cadastros')->where('lote_id',$l->id)->value('conciliacao_digest'),409,'Conciliação desatualizada.');
        return $p;
    }

    private function publicarEntidadeCadastro(object $l,object $e,string $tipo,array $projecao,array $meta): int
    {
        $tabela=match($tipo) {'turma'=>'turmas','aluno'=>'alunos'};
        $item=DB::table('copias_itens')->where('lote_id',$l->id)->where('entidade',$tipo)->where('origem_id',$projecao['origem_id'])->lockForUpdate()->first();
        if (!$item || $item->estado==='publicado') throw new \DomainException('Unidade divergente.');
        $mapa=DB::table('registros_origem')->where('origem_espaco_id',$l->origem_espaco_id)->where('destino_espaco_id',$l->destino_espaco_id)
            ->where('entidade',$tipo)->where('origem_id',$projecao['origem_id'])->lockForUpdate()->first();
        if ($mapa) $novo=(int)$mapa->destino_id; // Já validado sob SERIALIZABLE; nenhuma escrita no alvo.
        else {
            $novo=DB::table($tabela)->insertGetId($projecao['dados']+$meta+['created_at'=>now(),'updated_at'=>now()]);
            DB::table('registros_origem')->insert(['lote_id'=>$l->id,'entidade'=>$tipo,'origem_espaco_id'=>$l->origem_espaco_id,
                'origem_id'=>$projecao['origem_id'],'destino_espaco_id'=>$l->destino_espaco_id,'destino_id'=>$novo,'copiado_por'=>$e->ator_id,'created_at'=>now()]);
        }
        DB::table('copias_itens')->where('id',$item->id)->update(['estado'=>'publicado']);
        $this->eventoCadastro($e,$mapa?'item_reutilizado':'item_publicado',(int)$item->id);
        return $novo;
    }

    public function registrarFalhaExecucao(string $id): void
    {
        DB::transaction(function() use($id) {
            $this->lote($id,false); // Política própria e mesma ordem de locks do worker.
            $e=DB::table('copias_execucoes')->where('lote_id',$id)->lockForUpdate()->first();
            if (!$e || in_array($e->estado,['concluido','bloqueado'],true)) return;
            DB::table('copias_execucoes')->where('lote_id',$id)->increment('falhas',1,
                ['erro_tecnico'=>'execucao_falhou','proxima_tentativa_em'=>now()->addMinute(),'updated_at'=>now()]);
            $this->eventoCadastro($e,'execucao_falhou');
        });
    }
    private function bloquearCadastro(object $e,string $codigo): string
    {
        DB::table('copias_execucoes')->where('lote_id',$e->lote_id)->update(['estado'=>'bloqueado','erro_tecnico'=>$codigo,'updated_at'=>now()]);
        DB::table('copias_lotes')->where('id',$e->lote_id)->update(['estado'=>'bloqueado','updated_at'=>now()]);
        $this->eventoCadastro($e,'bloqueado'); return 'bloqueado';
    }
    private function eventoCadastro(object $e,string $acao,?int $item=null): void
    {
        OutboxCopia::persistir(EventoCopia::sistema(json_decode($e->contexto_evento,true,32,JSON_THROW_ON_ERROR),$acao,$item));
    }
    private function execucaoHabilitada(): bool
    {
        return config('copias.ativo')===true && config('copias.execucao_ativa')===true && config('copias.cadastro_execucao_ativa')===true;
    }
}
