<?php
namespace App\Services\Concerns;
use App\Models\Usuario;
use App\Services\{RecebimentosCopia,SelecaoMaterialCopia};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait DecideMaterialInterface
{
    public function criarViaRecebimento(Usuario $ator,int $origem,string $codigo,string $destinoDigest,string $conteudoDigest,string $chave,array $provas): string
    {
        RecebimentosCopia::exigirInterface();
        abort_unless(Str::isUuid($chave) && array_is_list($provas) && count($provas)>=1 && count($provas)<=20,422,'Seleção inválida.');
        foreach ($provas as $id) abort_unless(is_int($id) && $id>0,422,'Seleção inválida.');
        abort_unless(count(array_unique($provas))===count($provas),422,'Seleção inválida.'); sort($provas); $chave=strtolower($chave);
        return $this->transacao(function() use($ator,$origem,$codigo,$destinoDigest,$conteudoDigest,$chave,$provas) {
            $s=new RecebimentosCopia; $r=$s->resolverDentro($ator,$origem,$codigo,true);
            abort_unless(hash_equals($s->apresentar($r)['destino_digest'],$destinoDigest),409,'Destino mudou; confira novamente.');
            if ($r->lote_id) {
                $l=DB::table('copias_lotes')->where('id',$r->lote_id)->first();
                abort_unless($l && $l->chave===$chave && hash_equals($r->conteudo_confirmado_digest??'',$conteudoDigest),409,'Indicação já utilizada.');
                $id=$this->criarDentro($ator,$origem,(int)$r->destino_espaco_id,$chave,$provas);
                abort_unless($id===$r->lote_id,409,'Indicação já utilizada.'); return $id;
            }
            $p=(new SelecaoMaterialCopia)->previa($ator,$origem,$provas);
            abort_unless(hash_equals($p['conteudo_digest'],$conteudoDigest),409,'Seleção mudou; confira novamente.');
            // Uma chave já usada não pode consumir outra indicação, mesmo para os mesmos espaços.
            abort_if(DB::table('copias_lotes')->where('chave',$chave)->exists(),409,'Chave já utilizada.');
            $id=$this->criarDentro($ator,$origem,(int)$r->destino_espaco_id,$chave,$provas);
            DB::table('copias_recebimentos')->where('id',$r->id)->update(['lote_id'=>$id,'conteudo_confirmado_digest'=>$conteudoDigest]); return $id;
        });
    }

    public function previaInterface(Usuario $ator,string $id,string $lado): array
    {
        RecebimentosCopia::exigirInterface();
        return $this->transacao(function() use($ator,$id,$lado) {
            $l=$this->lote($id); $this->exigirInterfaceMaterial($ator,$l,$lado); $p=$this->projecao($l);
            $decisao=DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado',$lado)->first();
            // IDs dos objetos de origem são necessários somente à origem, não ao destinatário.
            if ($lado==='destino') foreach ($p['itens'] as &$i) unset($i['origem_id']);
            unset($i); unset($p['selecao_digest']);
            return $p+['lado'=>$lado,'origem_nome'=>DB::table('espacos')->where('id',$l->origem_espaco_id)->value('nome'),
                'destino_nome'=>DB::table('espacos')->where('id',$l->destino_espaco_id)->value('nome'),
                'revisado'=>$this->revisaoValida($l),'aprovado'=>$decisao!==null,
                'saida_valida_ate'=>$lado==='destino'?DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado','origem')->value('valida_ate'):null,
                'valida_ate'=>$decisao?->valida_ate,'estado'=>$l->estado,'autoriza_execucao'=>false];
        });
    }

    public function aprovarInterface(Usuario $ator,string $id,string $lado,string $digest,CarbonImmutable $validade): array
    {
        RecebimentosCopia::exigirInterface();
        abort_unless($validade->isFuture() && $validade->lessThanOrEqualTo(CarbonImmutable::now()->addDay()),422,'Prazo inválido.');
        return $this->transacao(function() use($ator,$id,$lado,$digest,$validade) {
            $l=$this->lote($id); $this->exigirInterfaceMaterial($ator,$l,$lado);
            abort_unless(hash_equals($this->projecao($l)['conteudo_digest'],$digest),409,'Prévia desatualizada.');
            if ($lado==='destino') {
                $ate=DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado','origem')->value('valida_ate');
                abort_unless($validade->lessThanOrEqualTo(CarbonImmutable::parse($ate,'UTC')),422,'Prazo excede a saída.');
            }
            return $this->aprovarDentro($ator,$id,$lado,$validade);
        });
    }

    public function revisarInterface(Usuario $ator,string $id,string $digest,bool $semDadosPessoais): string
    {
        RecebimentosCopia::exigirInterface();
        return $this->transacao(function() use($ator,$id,$digest,$semDadosPessoais) {
            $l=$this->lote($id); $this->exigirInterfaceMaterial($ator,$l,'origem');
            return $this->revisarDentro($ator,$id,$digest,$semDadosPessoais);
        });
    }

    private function exigirInterfaceMaterial(Usuario $ator,object $l,string $lado): void
    {
        $this->contexto($ator,$l,$lado);
        abort_unless(DB::table('copias_recebimentos')->where('lote_id',$l->id)->where('finalidade','preparacao_material')->exists(),404,'Solicitação indisponível.');
        abort_unless(in_array($l->estado,['aguardando_autorizacao','aguardando_auditoria'],true),409,'Solicitação indisponível.');
        if ($lado==='destino') abort_unless($this->origemValidaInterface($l),404,'Solicitação indisponível.');
        abort_unless($this->fonteAtual($l),409,'Seleção mudou; prepare novo lote.');
    }
    private function origemValidaInterface(object $l): bool
    {
        $d=DB::table('copias_autorizacoes')->where('lote_id',$l->id)->where('lado','origem')->first();
        if (!$d || $d->revogada_em || !CarbonImmutable::parse($d->valida_ate,'UTC')->isFuture() || $d->selecao_digest!==$l->selecao_digest) return false;
        try {
            $u=Usuario::find($d->ator_id);
            return $u && hash_equals($d->versao_acesso,$this->contexto($u->noVinculo($d->vinculo_id),$l,'origem')['versao_acesso']) && $this->revisaoValida($l);
        } catch(HttpException) { return false; }
    }
}
