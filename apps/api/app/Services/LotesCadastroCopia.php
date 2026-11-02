<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoVinculo,ElegibilidadeCopia,Espacos,RegimeVinculos};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{Crypt,DB};
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Contrato interno de cadastro. Não publica alunos/turmas nem expõe documentos. */
final class LotesCadastroCopia
{
    public const POLITICA='t5-cadastro-v1';

    public function criar(Usuario $ator,int $origem,int $destino,string $chave,array $turmas,array $alunos): string
    {
        abort_unless(Str::isUuid($chave) && $origem!==$destino,422,'Seleção inválida.');
        foreach ([$turmas,$alunos] as $ids) {
            abort_unless(array_is_list($ids),422);
            foreach ($ids as $id) abort_unless(is_int($id) && $id>0,422);
            abort_unless(count(array_unique($ids))===count($ids),422);
        }
        abort_unless(count($turmas)+count($alunos)>=1 && count($turmas)+count($alunos)<=100,422);
        sort($turmas); sort($alunos); $chave=strtolower($chave);
        return $this->transacao(function() use($ator,$origem,$destino,$chave,$turmas,$alunos) {
            $c=ElegibilidadeCopia::lado($ator,$origem,'origem');
            $d=DB::table('espacos')->find($destino);
            abort_unless($d && in_array($d->tipo,['pessoal','escola'],true) && ContextoVinculo::espacoValido($d),403,'Destino indisponível.');
            $pedido=$this->digest([$c['usuario_id'],$c['vinculo_id'],$origem,$destino,self::POLITICA,$turmas,$alunos]);
            $existente=DB::table('copias_lotes')->where('chave',$chave)->lockForUpdate()->first();
            if ($existente) { abort_unless(hash_equals($existente->pedido_digest,$pedido),409,'Chave já utilizada.'); return $existente->id; }
            $p=(new PreviaCadastroCopia)->previaOrigem($ator,$origem,$d->tipo,$turmas,$alunos);
            $itens=$this->itens($p); $id=(string)Str::uuid();
            DB::table('copias_lotes')->insert(['id'=>$id,'chave'=>$chave,'origem_espaco_id'=>$origem,'destino_espaco_id'=>$destino,
                'solicitante_id'=>$c['usuario_id'],'solicitante_vinculo_id'=>$c['vinculo_id'],'finalidade'=>'cadastro_minimo',
                'politica'=>self::POLITICA,'pedido_digest'=>$pedido,'selecao_digest'=>$this->selecao($origem,$destino,$itens),
                'estado'=>'aguardando_autorizacao','total_itens'=>count($itens),'auditoria_versao'=>1,'created_at'=>now(),'updated_at'=>now()]);
            foreach ($itens as $item) DB::table('copias_itens')->insert($item+['lote_id'=>$id,'origem_espaco_id'=>$origem,'destino_espaco_id'=>$destino]);
            DB::table('copias_cadastros')->insert(['lote_id'=>$id,'conteudo_digest'=>$this->conteudo($p)]);
            OutboxCopia::registrar($id,$ator,'solicitado'); return $id;
        });
    }

    public function previaOrigem(Usuario $ator,string $id): array
    {
        return $this->transacao(function() use($ator,$id) {
            $l=$this->lote($id); $this->contexto($ator,$l,'origem'); $p=$this->fonte($l);
            return ['lote_id'=>$id,'conteudo_digest'=>$this->conteudo($p),'turmas'=>$p['turmas'],'alunos'=>$p['alunos'],
                'pendencias'=>$p['pendencias'],'autoriza_execucao'=>false];
        });
    }

    public function revisarEvidencia(Usuario $ator,string $id,string $digest,string $documento,bool $aplicabilidadeRevisada,CarbonImmutable $validaAte): string
    {
        abort_unless($aplicabilidadeRevisada && trim($documento)!=='' && strlen($documento)<=65536
            && filter_var(trim($documento),FILTER_VALIDATE_URL)===false,422,'Evidência e revisão explícita obrigatórias.');
        $validade=$this->validade($validaAte);
        return $this->transacao(function() use($ator,$id,$digest,$documento,$validade) {
            $l=$this->lote($id); $c=$this->contexto($ator,$l,'origem'); $p=$this->fonte($l);
            abort_unless(hash_equals($this->conteudo($p),$digest),409,'Prévia desatualizada.');
            $hash=hash('sha256',$documento);
            $e=DB::table('copias_evidencias')->where('lote_id',$id)->first();
            if ($e) {
                abort_unless($this->evidenciaValida($l) && (int)$e->ator_id===$c['usuario_id'] && (int)$e->vinculo_id===$c['vinculo_id']
                    && $e->documento_digest===$hash && $e->valida_ate===$validade,409,'Evidência já registrada.');
                return $e->id;
            }
            $eid=(string)Str::uuid();
            DB::table('copias_evidencias')->insert(['id'=>$eid,'lote_id'=>$id,'ator_id'=>$c['usuario_id'],'vinculo_id'=>$c['vinculo_id'],
                'versao_acesso'=>$c['versao_acesso'],'selecao_digest'=>$l->selecao_digest,'conteudo_digest'=>$digest,
                'categorias'=>json_encode($this->categorias($p),JSON_THROW_ON_ERROR),'documento_cifrado'=>Crypt::encryptString($documento),
                'documento_digest'=>$hash,'confirmacao'=>'aplicabilidade_revisada','valida_ate'=>$validade,'created_at'=>now()]);
            OutboxCopia::registrar($id,$ator,'revisado'); return $eid;
        });
    }

    public function aprovar(Usuario $ator,string $id,string $lado,CarbonImmutable $validaAte,?string $conciliacaoDigest=null): string
    {
        $validade=$this->validade($validaAte);
        return $this->transacao(function() use($ator,$id,$lado,$validade,$conciliacaoDigest) {
            $l=$this->lote($id); $c=$this->contexto($ator,$l,$lado);
            abort_unless($this->evidenciaValida($l),409,'Evidência indisponível.');
            $e=DB::table('copias_evidencias')->where('lote_id',$id)->first();
            abort_unless($validade<=$e->valida_ate,422,'Prazo excede a evidência.');
            $p=$this->fonte($l); abort_unless($p['pendencias']===[],409,'Cadastro exige complementação autorizada.');
            if ($lado==='destino') {
                abort_unless($this->decisaoValida($l,'origem'),409,'Saída não autorizada.');
                $r=$this->conflitos($l,$p);
                abort_unless($r['sem_bloqueios'] && is_string($conciliacaoDigest) && hash_equals($r['digest'],$conciliacaoDigest),409,'Conciliação pendente ou desatualizada.');
            }
            $anterior=DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado',$lado)->first();
            if ($anterior) {
                abort_unless($this->decisaoValida($l,$lado) && (int)$anterior->ator_id===$c['usuario_id'] && (int)$anterior->vinculo_id===$c['vinculo_id']
                    && $anterior->valida_ate===$validade,409,'Decisão já registrada.');
                if ($lado==='destino') abort_unless(DB::table('copias_cadastros')->where('lote_id',$id)->value('conciliacao_digest')===$conciliacaoDigest,409);
                return $anterior->id;
            }
            $decisao=(string)Str::uuid();
            DB::table('copias_autorizacoes')->insert(['id'=>$decisao,'lote_id'=>$id,'origem_espaco_id'=>$l->origem_espaco_id,
                'destino_espaco_id'=>$l->destino_espaco_id,'lado'=>$lado,'ator_id'=>$c['usuario_id'],'vinculo_id'=>$c['vinculo_id'],
                'versao_acesso'=>$c['versao_acesso'],'selecao_digest'=>$l->selecao_digest,'valida_ate'=>$validade,'created_at'=>now()]);
            if ($lado==='destino') {
                DB::table('copias_cadastros')->where('lote_id',$id)->update(['conciliacao_digest'=>$conciliacaoDigest]);
                DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'aguardando_auditoria','updated_at'=>now()]);
            }
            OutboxCopia::registrar($id,$ator,'aprovado',$decisao); return $decisao;
        });
    }

    public function conciliar(Usuario $ator,string $id): array
    {
        return $this->transacao(function() use($ator,$id) {
            $l=$this->lote($id); $this->contexto($ator,$l,'destino');
            abort_unless($this->evidenciaValida($l) && $this->decisaoValida($l,'origem'),409,'Saída não autorizada.');
            return $this->conflitos($l,$this->fonte($l))+['autoriza_execucao'=>false];
        });
    }

    public function revalidar(Usuario $ator,string $id): bool
    {
        return $this->transacao(function() use($ator,$id) {
            $l=$this->lote($id); $this->contexto($ator,$l,'destino');
            $valido=$this->evidenciaValida($l) && $this->decisaoValida($l,'origem') && $this->decisaoValida($l,'destino');
            try {
                if ($valido) {
                    $r=$this->conflitos($l,$this->fonte($l));
                    $valido=$r['sem_bloqueios'] && $r['digest']===DB::table('copias_cadastros')->where('lote_id',$id)->value('conciliacao_digest');
                }
            } catch (HttpException $e) { if (!in_array($e->getStatusCode(),[403,404,409,422],true)) throw $e; $valido=false; }
            if (!$valido) {
                DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'bloqueado','updated_at'=>now()]);
                OutboxCopia::registrar($id,$ator,'bloqueado'); return false;
            }
            return (new OutboxCopia)->confirmada($id); // Sinal técnico; C3 ainda não existe.
        });
    }

    public function revogar(Usuario $ator,string $id,string $lado): void
    {
        $this->transacao(function() use($ator,$id,$lado) {
            $l=$this->lote($id,false); $this->contexto($ator,$l,$lado);
            if ($l->estado==='bloqueado') return;
            $c=$this->contexto($ator,$l,$lado);
            DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado',$lado)->whereNull('revogada_em')->update([
                'revogada_em'=>now(),'revogada_por'=>$c['usuario_id'],'revogada_vinculo_id'=>$c['vinculo_id'],'revogada_versao_acesso'=>$c['versao_acesso']]);
            if ($lado==='origem') DB::table('copias_evidencias')->where('lote_id',$id)->whereNull('revogada_em')->update(['revogada_em'=>now()]);
            DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'bloqueado','updated_at'=>now()]);
            OutboxCopia::registrar($id,$ator,'revogado');
        });
    }

    private function conflitos(object $l,array $p): array
    {
        $itens=[]; $mapas=[]; $pais=[]; $bloqueado=$p['pendencias']!==[];
        foreach (['turmas'=>'turma','alunos'=>'aluno'] as $grupo=>$entidade) foreach ($p[$grupo] as $i) {
            $mapa=DB::table('registros_origem')->where('origem_espaco_id',$l->origem_espaco_id)->where('destino_espaco_id',$l->destino_espaco_id)
                ->where('entidade',$entidade)->where('origem_id',$i['origem_id'])->first();
            $codigo='novo'; $alvo=null;
            if ($mapa) {
                $publicado=DB::table('copias_itens')->where('lote_id',$mapa->lote_id)->where('entidade',$entidade)
                    ->where('origem_id',$i['origem_id'])->where('estado','publicado')->exists();
                $q=DB::table($grupo)->where('id',$mapa->destino_id)->where('espaco_id',$l->destino_espaco_id);
                if ($entidade==='turma') $q=Espacos::conferirEscola($q,'turmas');
                $alvo=$publicado?$q->first():null; $codigo=$alvo?'reutilizar_mapa':'mapa_invalido';
                if ($alvo && $entidade==='aluno' && ($pais[$i['turma_origem_id']]??null)!==(int)$alvo->turma_id) $codigo='dependencia_incompativel';
                // Hash interno detecta mudança do mapeamento, sem expor os IDs existentes.
                $mapas[]=[$entidade,$i['origem_id'],(int)$mapa->id,(int)$mapa->destino_id,$codigo];
            } elseif ($entidade==='aluno' && DB::table('alunos')->where('espaco_id',$l->destino_espaco_id)->where('matricula',$i['dados']['matricula'])->exists()) {
                $codigo='conflito_matricula';
            }
            if ($entidade==='turma') $pais[$i['origem_id']]=$alvo?(int)$alvo->id:null;
            if (!in_array($codigo,['novo','reutilizar_mapa'],true)) $bloqueado=true;
            $itens[]=['entidade'=>$entidade,'origem_id'=>$i['origem_id'],'codigo'=>$codigo];
        }
        return ['itens'=>$itens,'pendencias'=>$p['pendencias'],'sem_bloqueios'=>!$bloqueado,
            'digest'=>$this->digest([$l->selecao_digest,$itens,$p['pendencias'],$mapas])];
    }

    private function fonte(object $l): array
    {
        $u=Usuario::find($l->solicitante_id); abort_unless($u,409,'Origem indisponível.');
        $itens=DB::table('copias_itens')->where('lote_id',$l->id)->get();
        $ids=fn($tipo)=>$itens->where('entidade',$tipo)->pluck('origem_id')->map(fn($id)=>(int)$id)->values()->all();
        $d=DB::table('espacos')->find($l->destino_espaco_id);
        abort_unless($d && ContextoVinculo::espacoValido($d),409,'Destino indisponível.');
        $p=(new PreviaCadastroCopia)->previaOrigem($u->noVinculo($l->solicitante_vinculo_id),(int)$l->origem_espaco_id,$d->tipo,$ids('turma'),$ids('aluno'));
        abort_unless(count($itens)===(int)$l->total_itens && hash_equals($l->selecao_digest,$this->selecao((int)$l->origem_espaco_id,(int)$l->destino_espaco_id,$this->itens($p)))
            && $this->conteudo($p)===DB::table('copias_cadastros')->where('lote_id',$l->id)->value('conteudo_digest'),409,'Seleção mudou; prepare novo lote.');
        return $p;
    }
    private function evidenciaValida(object $l): bool
    {
        $e=DB::table('copias_evidencias')->where('lote_id',$l->id)->first();
        if (!$e || $e->revogada_em || !CarbonImmutable::parse($e->valida_ate,'UTC')->isFuture() || $e->selecao_digest!==$l->selecao_digest) return false;
        try {
            $u=Usuario::find($e->ator_id); if (!$u) return false;
            $c=$this->contexto($u->noVinculo($e->vinculo_id),$l,'origem');
            $p=$this->fonte($l);
            return hash_equals($e->versao_acesso,$c['versao_acesso']) && $e->confirmacao==='aplicabilidade_revisada'
                && $e->conteudo_digest===DB::table('copias_cadastros')->where('lote_id',$l->id)->value('conteudo_digest')
                && json_decode($e->categorias,true,16,JSON_THROW_ON_ERROR)===$this->categorias($p)
                && hash_equals($e->documento_digest,hash('sha256',Crypt::decryptString($e->documento_cifrado)));
        } catch (HttpException|\JsonException|\Illuminate\Contracts\Encryption\DecryptException) { return false; }
    }
    private function decisaoValida(object $l,string $lado): bool
    {
        $d=DB::table('copias_autorizacoes')->where('lote_id',$l->id)->where('lado',$lado)->first();
        if (!$d || $d->revogada_em || !CarbonImmutable::parse($d->valida_ate,'UTC')->isFuture() || $d->selecao_digest!==$l->selecao_digest) return false;
        try {
            $u=Usuario::find($d->ator_id); if (!$u) return false;
            return hash_equals($d->versao_acesso,$this->contexto($u->noVinculo($d->vinculo_id),$l,$lado)['versao_acesso']);
        } catch (HttpException) { return false; }
    }
    private function contexto(Usuario $u,object $l,string $lado): array
    {
        abort_unless(in_array($lado,['origem','destino'],true),422);
        return ElegibilidadeCopia::lado($u,(int)$l->{$lado.'_espaco_id'},$lado);
    }
    private function lote(string $id,bool $aberto=true): object
    {
        $l=DB::table('copias_lotes')->where('id',$id)->lockForUpdate()->first();
        abort_unless($l && $l->politica===self::POLITICA && $l->finalidade==='cadastro_minimo' && (int)$l->auditoria_versao===1,404,'Lote indisponível.');
        abort_if($aberto && $l->estado==='bloqueado',409,'Lote bloqueado.'); return $l;
    }
    private function itens(array $p): array
    {
        $r=[]; foreach (['turmas'=>'turma','alunos'=>'aluno'] as $grupo=>$tipo) foreach ($p[$grupo] as $i) {
            $r[]=['entidade'=>$tipo,'origem_id'=>$i['origem_id'],'fonte_digest'=>$this->digest($i)];
        } return $r;
    }
    private function categorias(array $p): array
    {
        $r=[]; foreach (['turmas','alunos'] as $grupo) if ($p[$grupo]!==[]) {
            $r[$grupo]=array_keys($p[$grupo][0]['dados']);
            if ($grupo==='alunos' && $p['tipo_destino']==='escola' && !in_array('data_nascimento',$r[$grupo],true)) $r[$grupo][]='data_nascimento';
        } return $r;
    }
    private function selecao(int $o,int $d,array $itens): string { return $this->digest([self::POLITICA,'cadastro_minimo',$o,$d,$itens]); }
    private function conteudo(array $p): string { return $this->digest([$p['tipo_destino'],$p['turmas'],$p['alunos'],$p['pendencias']]); }
    private function digest(array $dados): string { return hash('sha256',json_encode($dados,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); }
    private function validade(CarbonImmutable $v): string
    {
        $s=$v->utc()->format('Y-m-d H:i:s'); abort_unless(CarbonImmutable::parse($s,'UTC')->isFuture(),422,'Validade inválida.'); return $s;
    }
    private function transacao(callable $f): mixed
    {
        abort_unless(config('copias.ativo')===true && Espacos::ativo() && RegimeVinculos::obrigatorio(),403,'Cópia indisponível.');
        if (DB::transactionLevel()!==0) throw new \LogicException('Cadastro exige transação própria.');
        $driver=DB::connection()->getDriverName();
        if (in_array($driver,['mysql','mariadb'],true)) DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        elseif ($driver!=='sqlite' || !app()->environment('testing')) throw new \LogicException('Cadastro exige MariaDB.');
        return DB::transaction($f);
    }
}
