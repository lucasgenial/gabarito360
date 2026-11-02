<?php
namespace App\Services\Concerns;
use App\Models\Usuario;
use App\Services\{RecebimentosCopia,PacoteResultadosCopia};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait DecideResultadosInterface
{
    public function prepararInterface(Usuario $ator,int $origem,string $codigo,array $notas): array
    {
        RecebimentosCopia::exigirInterface(); $notas=$this->ids($notas,20); abort_if($notas===[],422);
        return $this->transacao(function() use($ator,$origem,$codigo,$notas) {
            $s=new RecebimentosCopia('resultados_identificados'); $r=$s->resolverDentro($ator,$origem,$codigo); $d=$s->apresentar($r);
            $p=(new PacoteResultadosCopia)->capturar($ator,$origem,(int)$r->destino_espaco_id,$d['destino']['tipo'],$notas);
            abort_if(strlen($this->json($p))>524288,422,'Pacote excede o limite.');
            return ['espaco_id'=>$origem,'notas'=>$notas,'dependencias'=>$p['dependencias'],'recebimento'=>$d,
                'pacote'=>$this->apresentarPacoteInterface($p),'conteudo_digest'=>$this->digest($p),'autoriza_execucao'=>false];
        });
    }
    public function criarViaRecebimento(Usuario $ator,int $origem,string $codigo,string $destinoDigest,string $conteudoDigest,string $chave,array $notas,array $dependencias): string
    {
        RecebimentosCopia::exigirInterface(); abort_unless(Str::isUuid($chave),422);
        $notas=$this->ids($notas,20); abort_if($notas===[],422);
        abort_unless(count($dependencias)===3 && array_diff(['provas','alunos','turmas'],array_keys($dependencias))===[],422);
        $deps=[]; foreach(['provas','alunos','turmas'] as $k) { abort_unless(is_array($dependencias[$k]),422); $deps[$k]=$this->ids($dependencias[$k],20); }
        $chave=strtolower($chave);
        return $this->transacao(function() use($ator,$origem,$codigo,$destinoDigest,$conteudoDigest,$chave,$notas,$deps) {
            $s=new RecebimentosCopia('resultados_identificados'); $r=$s->resolverDentro($ator,$origem,$codigo,true); $d=$s->apresentar($r);
            abort_unless(hash_equals($d['destino_digest'],$destinoDigest),409,'Destino mudou; confira novamente.');
            if($r->lote_id) {
                $l=DB::table('copias_lotes')->where('id',$r->lote_id)->first();
                abort_unless($l && $l->chave===$chave && hash_equals($r->conteudo_confirmado_digest??'',$conteudoDigest),409,'Indicação já utilizada.');
                $id=$this->criarDentro($ator,$origem,(int)$r->destino_espaco_id,$chave,$notas,$deps); abort_unless($id===$r->lote_id,409); return $id;
            }
            $p=(new PacoteResultadosCopia)->capturar($ator,$origem,(int)$r->destino_espaco_id,$d['destino']['tipo'],$notas);
            abort_unless($p['dependencias']===$deps,422,'Confirme exatamente as dependências.');
            abort_unless(hash_equals($this->digest($p),$conteudoDigest),409,'Pacote mudou; confira novamente.');
            abort_if(DB::table('copias_lotes')->where('chave',$chave)->exists(),409);
            $id=$this->criarDentro($ator,$origem,(int)$r->destino_espaco_id,$chave,$notas,$deps);
            DB::table('copias_recebimentos')->where('id',$r->id)->update(['lote_id'=>$id,'conteudo_confirmado_digest'=>$conteudoDigest]); return $id;
        });
    }
    public function previaInterface(Usuario $ator,string $id,string $lado): array
    {
        RecebimentosCopia::exigirInterface();
        return $this->transacao(function() use($ator,$id,$lado) {
            $l=$this->lote($id); $this->exigirInterfaceResultados($ator,$l,$lado); $p=$this->fonte($l);
            $e=DB::table('copias_evidencias')->where('lote_id',$id)->first(['valida_ate']);
            $d=DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado',$lado)->first(['valida_ate']);
            return ['lote_id'=>$id,'lado'=>$lado,'pacote'=>$this->apresentarPacoteInterface($p),'conteudo_digest'=>$this->digest($p),
                'evidencia_registrada'=>$e!==null,'evidencia_valida'=>$this->evidenciaValida($l),'evidencia_valida_ate'=>$e?->valida_ate,
                'decisao_registrada'=>$d!==null,'decisao_valida'=>$this->decisaoValida($l,$lado),'valida_ate'=>$d?->valida_ate,
                'saida_valida_ate'=>$lado==='destino'?DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado','origem')->value('valida_ate'):null,
                'origem_nome'=>DB::table('espacos')->where('id',$l->origem_espaco_id)->value('nome'),
                'destino_nome'=>DB::table('espacos')->where('id',$l->destino_espaco_id)->value('nome'),'autoriza_execucao'=>false];
        });
    }
    public function evidenciaInterface(Usuario $ator,string $id,string $digest,string $documento,string $guarda,string $preservacao,bool $confirmado,bool $legado,CarbonImmutable $ate): string
    {
        RecebimentosCopia::exigirInterface(); $this->prazoInterfaceResultados($ate);
        abort_unless($confirmado && $legado && trim($documento)!=='' && strlen($documento)<=65536 && filter_var(trim($documento),FILTER_VALIDATE_URL)===false
            && trim($guarda)!=='' && strlen($guarda)<=2000 && trim($preservacao)!=='' && strlen($preservacao)<=2000,422,'Evidência, guarda, preservação e reconhecimento obrigatórios.');
        $doc=$this->json(['politica'=>self::POLITICA,'documento'=>$documento,'guarda_contratual'=>$guarda,'preservacao_excepcional'=>$preservacao,'legado_reconhecido'=>true]);
        return $this->transacao(function() use($ator,$id,$digest,$doc,$ate) {
            $l=$this->lote($id); $this->exigirInterfaceResultados($ator,$l,'origem');
            return $this->evidenciaDentro($ator,$id,$digest,$doc,$this->validade($ate));
        });
    }
    public function aprovarInterface(Usuario $ator,string $id,string $lado,string $digest,bool $legado,CarbonImmutable $ate): string
    {
        RecebimentosCopia::exigirInterface(); $this->prazoInterfaceResultados($ate); abort_unless($legado,422,'Reconhecimento do legado obrigatório.');
        return $this->transacao(function() use($ator,$id,$lado,$digest,$ate) {
            $l=$this->lote($id); $this->exigirInterfaceResultados($ator,$l,$lado);
            if($lado==='destino') {
                $saida=DB::table('copias_autorizacoes')->where('lote_id',$id)->where('lado','origem')->value('valida_ate');
                abort_unless($ate->lessThanOrEqualTo(CarbonImmutable::parse($saida,'UTC')),422,'Prazo excede a saída.');
            }
            return $this->aprovarDentro($ator,$id,$lado,$digest,$this->validade($ate));
        });
    }
    private function prazoInterfaceResultados(CarbonImmutable $ate): void
    { abort_unless($ate->isFuture() && $ate->lessThanOrEqualTo(CarbonImmutable::now()->addDay()),422,'Prazo inválido.'); }
    private function exigirInterfaceResultados(Usuario $ator,object $l,string $lado): void
    {
        $this->contexto($ator,$l,$lado);
        abort_unless(DB::table('copias_recebimentos')->where('lote_id',$l->id)->where('finalidade','resultados_identificados')->exists(),404);
        abort_unless(in_array($l->estado,['aguardando_autorizacao','aguardando_auditoria'],true),409,'Solicitação indisponível.');
        if($lado==='destino') abort_unless($this->evidenciaValida($l) && $this->decisaoValida($l,'origem'),404,'Solicitação indisponível.');
    }
    private function apresentarPacoteInterface(array $p): array
    {
        $alunos=array_column($p['alunos'],null,'origem_id'); $turmas=array_column($p['turmas_aplicacao'],null,'origem_id');
        $materiais=array_column($p['materiais_observados'],null,'origem_id'); $resultados=[];
        foreach($p['resultados'] as $r) {
            $a=$alunos[$r['aluno_origem_id']]; $t=$turmas[$r['turma_origem_id']]; $m=$materiais[$r['prova_origem_id']]['observado'];
            $resultados[]=['aluno'=>$a['nome'],'matricula'=>$a['matricula'],'turma'=>$t['nome'],'prova'=>$m['prova']['titulo'],
                'dados'=>$r['dados'],'respostas'=>$r['respostas'],'versao_historica_comprovada'=>false];
        }
        return ['tipo_destino'=>$p['tipo_destino'],'condicao_historica'=>'legado_sem_versao_original','resultados'=>$resultados,
            'materiais'=>array_map(fn($m)=>['material'=>$m['observado']],$p['materiais_observados']),
            'alunos'=>array_map(fn($a)=>['nome'=>$a['nome'],'matricula'=>$a['matricula']],$p['alunos']),
            'turmas'=>array_map(fn($t)=>['nome'=>$t['nome'],'serie'=>$t['serie'],'turno'=>$t['turno'],'ano_letivo'=>$t['ano_letivo']],$p['turmas_aplicacao'])];
    }
}
