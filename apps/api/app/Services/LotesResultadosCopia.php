<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoVinculo,ElegibilidadeCopia,Espacos,RegimeVinculos};
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\{Crypt,DB};
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Pacote cifrado e decisões internas. Não publica nem agenda resultados. */
final class LotesResultadosCopia
{
    public const POLITICA='t5-resultados-v1';

    public function criar(Usuario $ator,int $origem,int $destino,string $chave,array $notas,array $dependencias): string
    {
        abort_unless(Str::isUuid($chave) && $origem!==$destino,422,'Seleção inválida.');
        $notas=$this->ids($notas,20); abort_unless($notas!==[],422,'Seleção vazia.');
        abort_unless(count($dependencias)===3 && array_diff(['provas','alunos','turmas'],array_keys($dependencias))===[],422,'Confirme as dependências.');
        $deps=[]; foreach (['provas','alunos','turmas'] as $k) {
            abort_unless(is_array($dependencias[$k]),422,'Dependências inválidas.');
            $deps[$k]=$this->ids($dependencias[$k],20);
        }
        $chave=strtolower($chave);
        return $this->transacao(function() use($ator,$origem,$destino,$chave,$notas,$deps) {
            $c=ElegibilidadeCopia::lado($ator,$origem,'origem'); $d=DB::table('espacos')->find($destino);
            abort_unless($d && in_array($d->tipo,['escola','pessoal'],true) && ContextoVinculo::espacoValido($d),403,'Destino indisponível.');
            $pedido=$this->digest([self::POLITICA,$c['usuario_id'],$c['vinculo_id'],$origem,$destino,$notas,$deps]);
            $anterior=DB::table('copias_lotes')->where('chave',$chave)->lockForUpdate()->first();
            if ($anterior) { abort_unless(hash_equals($anterior->pedido_digest,$pedido),409,'Chave já utilizada.'); return $anterior->id; }
            $p=(new PacoteResultadosCopia)->capturar($ator,$origem,$destino,$d->tipo,$notas);
            abort_unless($p['dependencias']===$deps,422,'Confirme exatamente as dependências selecionadas.');
            $json=$this->json($p); abort_if(strlen($json)>524288,422,'Pacote excede o limite.');
            $id=(string)Str::uuid();
            DB::table('copias_lotes')->insert(['id'=>$id,'chave'=>$chave,'origem_espaco_id'=>$origem,'destino_espaco_id'=>$destino,
                'solicitante_id'=>$c['usuario_id'],'solicitante_vinculo_id'=>$c['vinculo_id'],'finalidade'=>'resultados_identificados',
                'politica'=>self::POLITICA,'pedido_digest'=>$pedido,'selecao_digest'=>$this->selecao($p),
                'estado'=>'aguardando_autorizacao','total_itens'=>count($notas),'auditoria_versao'=>1,'created_at'=>now(),'updated_at'=>now()]);
            foreach ($p['resultados'] as $r) DB::table('copias_itens')->insert(['lote_id'=>$id,'origem_espaco_id'=>$origem,
                'destino_espaco_id'=>$destino,'entidade'=>'nota','origem_id'=>$r['nota_origem_id'],'fonte_digest'=>$this->digest($r)]);
            DB::table('copias_resultados')->insert(['lote_id'=>$id,'conteudo_cifrado'=>Crypt::encryptString($json),
                'conteudo_digest'=>hash('sha256',$json),'solicitante_versao'=>$c['versao_acesso'],'capturado_em'=>now()->utc()]);
            OutboxCopia::registrar($id,$ator,'solicitado'); return $id;
        });
    }

    public function previa(Usuario $ator,string $id,string $lado): array
    {
        return $this->transacao(function() use($ator,$id,$lado) {
            $l=$this->lote($id); $this->contexto($ator,$l,$lado);
            if ($lado==='destino') abort_unless($this->evidenciaValida($l) && $this->decisaoValida($l,'origem'),409,'Saída não autorizada.');
            $p=$this->fonte($l);
            return ['lote_id'=>$id,'pacote'=>$p,'conteudo_digest'=>$this->digest($p),'autoriza_execucao'=>false];
        });
    }

    public function revisarEvidencia(Usuario $ator,string $id,string $digest,string $documento,string $guarda,
        string $preservacao,bool $aplicabilidadeRevisada,bool $legadoReconhecido,CarbonImmutable $validaAte): string
    {
        abort_unless($aplicabilidadeRevisada && $legadoReconhecido && trim($documento)!=='' && strlen($documento)<=65536
            && filter_var(trim($documento),FILTER_VALIDATE_URL)===false && trim($guarda)!=='' && strlen($guarda)<=2000
            && trim($preservacao)!=='' && strlen($preservacao)<=2000,422,'Evidência, guarda e reconhecimento do legado obrigatórios.');
        $validade=$this->validade($validaAte);
        $doc=$this->json(['politica'=>self::POLITICA,'documento'=>$documento,'guarda_contratual'=>$guarda,
            'preservacao_excepcional'=>$preservacao,'legado_reconhecido'=>true]);
        return $this->transacao(function() use($ator,$id,$digest,$doc,$validade) {
            $l=$this->lote($id); $c=$this->contexto($ator,$l,'origem'); $p=$this->fonte($l);
            abort_unless(hash_equals($this->digest($p),$digest),409,'Prévia desatualizada.');
            $hash=hash('sha256',$doc); $e=DB::table('copias_evidencias')->where('lote_id',$id)->first();
            if ($e) {
                abort_unless($this->evidenciaValida($l) && (int)$e->ator_id===$c['usuario_id'] && (int)$e->vinculo_id===$c['vinculo_id']
                    && $e->documento_digest===$hash && $e->valida_ate===$validade,409,'Evidência já registrada.'); return $e->id;
            }
            $eid=(string)Str::uuid();
            DB::table('copias_evidencias')->insert(['id'=>$eid,'lote_id'=>$id,'ator_id'=>$c['usuario_id'],'vinculo_id'=>$c['vinculo_id'],
                'versao_acesso'=>$c['versao_acesso'],'selecao_digest'=>$l->selecao_digest,'conteudo_digest'=>$digest,
                'categorias'=>$this->json(PacoteResultadosCopia::CATEGORIAS),'documento_cifrado'=>Crypt::encryptString($doc),
                'documento_digest'=>$hash,'confirmacao'=>'aplicabilidade_revisada','valida_ate'=>$validade,'created_at'=>now()]);
            OutboxCopia::registrar($id,$ator,'revisado'); return $eid;
        });
    }

    public function aprovar(Usuario $ator,string $id,string $lado,string $digest,bool $legadoReconhecido,CarbonImmutable $validaAte): string
    {
        abort_unless($legadoReconhecido,422,'Reconhecimento do legado obrigatório.'); $validade=$this->validade($validaAte);
        return $this->transacao(function() use($ator,$id,$lado,$digest,$validade) {
            $l=$this->lote($id); $c=$this->contexto($ator,$l,$lado); $p=$this->fonte($l);
            abort_unless(hash_equals($this->digest($p),$digest) && $this->evidenciaValida($l),409,'Evidência ou prévia indisponível.');
            $e=DB::table('copias_evidencias')->where('lote_id',$id)->first();
            abort_unless($validade<=$e->valida_ate,422,'Prazo excede a evidência.');
            if ($lado==='destino') abort_unless($this->decisaoValida($l,'origem'),409,'Saída não autorizada.');
            $a=DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado',$lado)->first();
            if ($a) {
                abort_unless($this->decisaoValida($l,$lado) && (int)$a->ator_id===$c['usuario_id'] && (int)$a->vinculo_id===$c['vinculo_id']
                    && $a->valida_ate===$validade,409,'Decisão já registrada.'); return $a->id;
            }
            $aid=(string)Str::uuid();
            DB::table('copias_autorizacoes')->insert(['id'=>$aid,'lote_id'=>$id,'origem_espaco_id'=>$l->origem_espaco_id,
                'destino_espaco_id'=>$l->destino_espaco_id,'lado'=>$lado,'ator_id'=>$c['usuario_id'],'vinculo_id'=>$c['vinculo_id'],
                'versao_acesso'=>$c['versao_acesso'],'selecao_digest'=>$l->selecao_digest,'valida_ate'=>$validade,'created_at'=>now()]);
            DB::table('copias_resultados_decisoes')->insert(['decisao_id'=>$aid,'conteudo_digest'=>$digest,'legado_reconhecido'=>true]);
            if ($lado==='destino') DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'aguardando_auditoria','updated_at'=>now()]);
            OutboxCopia::registrar($id,$ator,'aprovado',$aid); return $aid;
        });
    }

    public function situacao(Usuario $ator,string $id): array
    {
        return $this->transacao(function() use($ator,$id) {
            $l=$this->lote($id,false); $this->contexto($ator,$l,'destino'); $valido=false;
            try {
                if ($l->estado!=='bloqueado') { $this->fonte($l); $valido=$this->evidenciaValida($l) && $this->decisaoValida($l,'origem') && $this->decisaoValida($l,'destino'); }
            } catch (HttpException $e) { if (!in_array($e->getStatusCode(),[403,404,409,422],true)) throw $e; }
            return ['decisoes_validas'=>$valido,'auditoria_confirmada'=>$valido && (new OutboxCopia)->confirmada($id),
                'autoriza_execucao'=>false,'bloqueio'=>'execucao_resultados_pendente'];
        });
    }

    public function revogar(Usuario $ator,string $id,string $lado): void
    {
        $this->transacao(function() use($ator,$id,$lado) {
            $l=$this->lote($id,false); $c=$this->contexto($ator,$l,$lado);
            abort_if($l->estado==='concluido',409,'Lote concluído.'); if ($l->estado==='bloqueado') return;
            DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado',$lado)->whereNull('revogada_em')->update([
                'revogada_em'=>now(),'revogada_por'=>$c['usuario_id'],'revogada_vinculo_id'=>$c['vinculo_id'],'revogada_versao_acesso'=>$c['versao_acesso']]);
            if ($lado==='origem') DB::table('copias_evidencias')->where('lote_id',$id)->update(['revogada_em'=>now()]);
            DB::table('copias_lotes')->where('id',$id)->update(['estado'=>'bloqueado','updated_at'=>now()]);
            OutboxCopia::registrar($id,$ator,'revogado');
        });
    }

    private function fonte(object $l): array
    {
        $p=$this->pacote($l); $u=Usuario::find($l->solicitante_id); $d=DB::table('espacos')->find($l->destino_espaco_id);
        abort_unless($u && $d && ContextoVinculo::espacoValido($d),409,'Contexto indisponível.');
        $c=$this->contexto($u->noVinculo($l->solicitante_vinculo_id),$l,'origem');
        abort_unless(hash_equals(DB::table('copias_resultados')->where('lote_id',$l->id)->value('solicitante_versao'),$c['versao_acesso']),409,'Contexto mudou; prepare novo lote.');
        $atual=(new PacoteResultadosCopia)->capturar($u->noVinculo($l->solicitante_vinculo_id),(int)$l->origem_espaco_id,
            (int)$l->destino_espaco_id,$d->tipo,array_column($p['resultados'],'nota_origem_id'));
        abort_unless(hash_equals($this->digest($p),$this->digest($atual)),409,'Fonte mudou; prepare novo lote.'); return $p;
    }

    private function pacote(object $l): array
    {
        $r=DB::table('copias_resultados')->where('lote_id',$l->id)->first(); abort_unless($r,409,'Pacote indisponível.');
        try { $json=Crypt::decryptString($r->conteudo_cifrado); $p=json_decode($json,true,64,JSON_THROW_ON_ERROR); }
        catch (DecryptException|\JsonException) { abort(409,'Pacote indisponível.'); }
        abort_unless(is_array($p) && hash_equals($r->conteudo_digest,hash('sha256',$json))
            && ($p['politica']??null)===self::POLITICA && ($p['origem_espaco_id']??null)===(int)$l->origem_espaco_id
            && ($p['destino_espaco_id']??null)===(int)$l->destino_espaco_id && ($p['condicao_historica']??null)==='legado_sem_versao_original'
            && is_array($p['resultados']??null) && is_array($p['dependencias']??null),409,'Pacote divergente.');
        abort_unless(hash_equals($l->selecao_digest,$this->selecao($p)),409,'Seleção divergente.');
        $itens=DB::table('copias_itens')->where('lote_id',$l->id)->orderBy('origem_id')->get();
        abort_unless($itens->count()===(int)$l->total_itens && $itens->count()===count($p['resultados']),409,'Itens divergentes.');
        foreach ($itens as $i=>$item) abort_unless($item->entidade==='nota' && (int)$item->origem_id===$p['resultados'][$i]['nota_origem_id']
            && hash_equals($item->fonte_digest,$this->digest($p['resultados'][$i])),409,'Item divergente.');
        return $p;
    }

    private function evidenciaValida(object $l): bool
    {
        $e=DB::table('copias_evidencias')->where('lote_id',$l->id)->first();
        if (!$e || $e->revogada_em || !CarbonImmutable::parse($e->valida_ate,'UTC')->isFuture() || $e->selecao_digest!==$l->selecao_digest) return false;
        try {
            $u=Usuario::find($e->ator_id); if (!$u) return false; $c=$this->contexto($u->noVinculo($e->vinculo_id),$l,'origem');
            $doc=Crypt::decryptString($e->documento_cifrado); $campos=json_decode($doc,true,16,JSON_THROW_ON_ERROR);
            return hash_equals($e->versao_acesso,$c['versao_acesso']) && $e->confirmacao==='aplicabilidade_revisada'
                && $e->conteudo_digest===$this->digest($this->pacote($l)) && json_decode($e->categorias,true,16,JSON_THROW_ON_ERROR)===PacoteResultadosCopia::CATEGORIAS
                && hash_equals($e->documento_digest,hash('sha256',$doc)) && ($campos['politica']??null)===self::POLITICA
                && ($campos['legado_reconhecido']??false)===true;
        } catch (HttpException|DecryptException|\JsonException) { return false; }
    }
    private function decisaoValida(object $l,string $lado): bool
    {
        $a=DB::table('copias_autorizacoes')->where('lote_id',$l->id)->where('lado',$lado)->first();
        if (!$a || $a->revogada_em || !CarbonImmutable::parse($a->valida_ate,'UTC')->isFuture() || $a->selecao_digest!==$l->selecao_digest) return false;
        $r=DB::table('copias_resultados_decisoes')->where('decisao_id',$a->id)->first();
        if (!$r || !(bool)$r->legado_reconhecido || $r->conteudo_digest!==DB::table('copias_resultados')->where('lote_id',$l->id)->value('conteudo_digest')) return false;
        try { $u=Usuario::find($a->ator_id); return $u && hash_equals($a->versao_acesso,$this->contexto($u->noVinculo($a->vinculo_id),$l,$lado)['versao_acesso']); }
        catch (HttpException) { return false; }
    }
    private function contexto(Usuario $u,object $l,string $lado): array
    {
        abort_unless(in_array($lado,['origem','destino'],true),422,'Lado inválido.');
        return ElegibilidadeCopia::lado($u,(int)$l->{$lado.'_espaco_id'},$lado);
    }
    private function lote(string $id,bool $aberto=true): object
    {
        $l=DB::table('copias_lotes')->where('id',$id)->lockForUpdate()->first();
        abort_unless($l && $l->politica===self::POLITICA && $l->finalidade==='resultados_identificados' && (int)$l->auditoria_versao===1,404,'Lote indisponível.');
        abort_if($aberto && in_array($l->estado,['bloqueado','concluido'],true),409,'Lote fechado.'); return $l;
    }
    private function ids(array $ids,int $limite): array
    {
        abort_unless(array_is_list($ids) && count($ids)<=$limite,422,'Seleção inválida.');
        foreach ($ids as $id) abort_unless(is_int($id) && $id>0,422,'Seleção inválida.');
        abort_unless(count(array_unique($ids))===count($ids),422,'Seleção inválida.'); sort($ids); return $ids;
    }
    private function selecao(array $p): string
    { return $this->digest([self::POLITICA,$p['origem_espaco_id'],$p['destino_espaco_id'],array_column($p['resultados'],'nota_origem_id'),$p['dependencias']]); }
    private function json(array $p): string { return json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR); }
    private function digest(array $p): string { return hash('sha256',$this->json($p)); }
    private function validade(CarbonImmutable $v): string
    { $s=$v->utc()->format('Y-m-d H:i:s'); abort_unless(CarbonImmutable::parse($s,'UTC')->isFuture(),422,'Validade inválida.'); return $s; }
    private function transacao(callable $f): mixed
    {
        abort_unless(config('copias.ativo')===true && Espacos::ativo() && RegimeVinculos::obrigatorio(),403,'Cópia indisponível.');
        if (DB::transactionLevel()!==0) throw new \LogicException('Resultados exigem transação própria.');
        $driver=DB::connection()->getDriverName();
        if (in_array($driver,['mysql','mariadb'],true)) DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        elseif ($driver!=='sqlite' || !app()->environment('testing')) throw new \LogicException('Resultados exigem MariaDB.');
        return DB::transaction($f);
    }
}
