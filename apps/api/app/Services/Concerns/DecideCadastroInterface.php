<?php
namespace App\Services\Concerns;
use App\Models\Usuario;
use App\Services\{RecebimentosCopia,SelecaoCadastroCopia};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait DecideCadastroInterface
{
    public function criarViaRecebimento(Usuario $ator,int $origem,string $codigo,string $destinoDigest,string $conteudoDigest,string $chave,array $turmas,array $alunos): string
    {
        RecebimentosCopia::exigirInterface();
        abort_unless(Str::isUuid($chave) && array_is_list($turmas) && count($turmas)===1 && array_is_list($alunos) && count($alunos)<=20,422);
        foreach([$turmas,$alunos] as $ids) { foreach($ids as $id) abort_unless(is_int($id) && $id>0,422); abort_unless(count(array_unique($ids))===count($ids),422); }
        sort($alunos); $chave=strtolower($chave);
        return $this->transacao(function() use($ator,$origem,$codigo,$destinoDigest,$conteudoDigest,$chave,$turmas,$alunos) {
            $s=new RecebimentosCopia('cadastro_minimo'); $r=$s->resolverDentro($ator,$origem,$codigo,true); $destino=$s->apresentar($r);
            abort_unless(hash_equals($destino['destino_digest'],$destinoDigest),409,'Destino mudou; confira novamente.');
            if ($r->lote_id) {
                $l=DB::table('copias_lotes')->where('id',$r->lote_id)->first();
                abort_unless($l && $l->chave===$chave && hash_equals($r->conteudo_confirmado_digest??'',$conteudoDigest),409,'Indicação já utilizada.');
                $id=$this->criarDentro($ator,$origem,(int)$r->destino_espaco_id,$chave,$turmas,$alunos);
                abort_unless($id===$r->lote_id,409); return $id;
            }
            $p=(new SelecaoCadastroCopia)->previa($ator,$origem,$destino['destino']['tipo'],$turmas,$alunos);
            abort_unless(hash_equals($p['conteudo_digest'],$conteudoDigest),409,'Seleção mudou; confira novamente.');
            abort_if(DB::table('copias_lotes')->where('chave',$chave)->exists(),409,'Chave já utilizada.');
            $id=$this->criarDentro($ator,$origem,(int)$r->destino_espaco_id,$chave,$turmas,$alunos);
            DB::table('copias_recebimentos')->where('id',$r->id)->update(['lote_id'=>$id,'conteudo_confirmado_digest'=>$conteudoDigest]); return $id;
        });
    }
    public function previaInterface(Usuario $ator,string $id,string $lado): array
    {
        RecebimentosCopia::exigirInterface();
        return $this->transacao(function() use($ator,$id,$lado) {
            $l=$this->lote($id); $this->exigirInterfaceCadastro($ator,$l,$lado); $p=$this->fonte($l);
            $conciliacao=$lado==='destino'?$this->conflitos($l,$p):null;
            // A posição relaciona o conflito ao item exibido sem revelar IDs de objetos.
            if($conciliacao) {
                $conciliacao=['sem_bloqueios'=>$conciliacao['sem_bloqueios'],'digest'=>$conciliacao['digest'],'itens'=>array_map(
                    fn($i)=>['entidade'=>$i['entidade'],'codigo'=>$i['codigo']],$conciliacao['itens'])];
            }
            $dig=$this->conteudo($p);
            foreach(['turmas','alunos'] as $g) foreach($p[$g] as &$i) { unset($i['origem_id'],$i['turma_origem_id']); } unset($i);
            $e=DB::table('copias_evidencias')->where('lote_id',$id)->first(['valida_ate']);
            $d=DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado',$lado)->first(['valida_ate']);
            return ['lote_id'=>$id,'lado'=>$lado,'tipo_destino'=>$p['tipo_destino'],'turmas'=>$p['turmas'],'alunos'=>$p['alunos'],
                'tem_pendencias'=>$p['pendencias']!==[],'conteudo_digest'=>$dig,'conciliacao'=>$conciliacao,
                'evidencia_registrada'=>$e!==null,'evidencia_valida'=>$this->evidenciaValida($l),'evidencia_valida_ate'=>$e?->valida_ate,
                'decisao_registrada'=>$d!==null,'decisao_valida'=>$this->decisaoValida($l,$lado),'valida_ate'=>$d?->valida_ate,
                'saida_valida_ate'=>$lado==='destino'?DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado','origem')->value('valida_ate'):null,
                'origem_nome'=>DB::table('espacos')->where('id',$l->origem_espaco_id)->value('nome'),
                'destino_nome'=>DB::table('espacos')->where('id',$l->destino_espaco_id)->value('nome'),'autoriza_execucao'=>false];
        });
    }
    public function evidenciaInterface(Usuario $ator,string $id,string $digest,string $documento,bool $confirmado,CarbonImmutable $ate): string
    {
        RecebimentosCopia::exigirInterface(); $this->prazoInterfaceCadastro($ate);
        abort_unless($confirmado && trim($documento)!=='' && strlen($documento)<=65536 && filter_var(trim($documento),FILTER_VALIDATE_URL)===false,422,'Evidência e confirmação obrigatórias.');
        return $this->transacao(function() use($ator,$id,$digest,$documento,$ate) {
            $l=$this->lote($id); $this->exigirInterfaceCadastro($ator,$l,'origem');
            return $this->evidenciaDentro($ator,$id,$digest,$documento,$this->validade($ate));
        });
    }
    public function aprovarInterface(Usuario $ator,string $id,string $lado,string $digest,CarbonImmutable $ate,?string $conciliacao): string
    {
        RecebimentosCopia::exigirInterface(); $this->prazoInterfaceCadastro($ate);
        return $this->transacao(function() use($ator,$id,$lado,$digest,$ate,$conciliacao) {
            $l=$this->lote($id); $this->exigirInterfaceCadastro($ator,$l,$lado);
            abort_unless(hash_equals($this->conteudo($this->fonte($l)),$digest),409,'Prévia desatualizada.');
            if($lado==='destino') {
                $saida=DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado','origem')->value('valida_ate');
                abort_unless($ate->lessThanOrEqualTo(CarbonImmutable::parse($saida,'UTC')),422,'Prazo excede a saída.');
            }
            return $this->aprovarDentro($ator,$id,$lado,$this->validade($ate),$conciliacao);
        });
    }
    private function prazoInterfaceCadastro(CarbonImmutable $ate): void
    { abort_unless($ate->isFuture() && $ate->lessThanOrEqualTo(CarbonImmutable::now()->addDay()),422,'Prazo inválido.'); }
    private function exigirInterfaceCadastro(Usuario $ator,object $l,string $lado): void
    {
        $this->contexto($ator,$l,$lado);
        abort_unless(DB::table('copias_recebimentos')->where('lote_id',$l->id)->where('finalidade','cadastro_minimo')->exists(),404);
        abort_unless(in_array($l->estado,['aguardando_autorizacao','aguardando_auditoria'],true),409,'Solicitação indisponível.');
        if($lado==='destino') abort_unless($this->evidenciaValida($l) && $this->decisaoValida($l,'origem'),404,'Solicitação indisponível.');
    }
}
