<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoInterno,ContextoVinculo,DadoProtegido,Espacos,Permissao,TokenInterno};
use Illuminate\Support\{Carbon,Str};
use Illuminate\Support\Facades\{Crypt,DB,Validator};
use Illuminate\Validation\ValidationException;

final class AutorizacoesExportacaoTitular
{
    private const PERMISSAO = 'pedido.exportacao.autorizar';
    private function habilitar(): void
    {
        abort_unless(config('pedidos_titulares.ativo') && config('pedidos_titulares.autorizacoes_exportacao')
            && Espacos::ativo() && TokenInterno::habilitado(),403);
    }
    private function controle(): void
    {
        abort_unless(DB::table('acessos_internos_controle')->where('id',1)->lockForUpdate()->first(),503);
    }
    private function aprovador(Usuario $u): object
    {
        abort_unless(ContextoVinculo::presente($u) && !ContextoInterno::presente($u),403);
        abort_unless(DB::table('usuarios')->where('id',$u->id)->where('ativo',true)->where('forcar_troca_senha',false)->lockForUpdate()->first(),403);
        $registro=DB::table('usuario_vinculos')->where('id',$u->vinculoContextoId())->where('usuario_id',$u->id)
            ->where('situacao','ativo')->where('inicio_em','<=',now())
            ->where(fn($q)=>$q->whereNull('fim_em')->orWhere('fim_em','>',now()))->lockForUpdate()->first();
        abort_unless($registro,403);
        $espaco=DB::table('espacos')->where('id',$registro->espaco_id)->lockForUpdate()->first();
        $ajustes=DB::table('vinculo_permissoes')->where('vinculo_id',$u->vinculoContextoId())->lockForUpdate()->get();
        $v=ContextoVinculo::resolver($u);
        // Current reads acima prevalecem sobre eventual snapshot REPEATABLE READ do consumidor.
        abort_unless((int)$v->versao_autorizacao===(int)$registro->versao_autorizacao && $v->perfil===$registro->perfil
            && $v->inicio_em===$registro->inicio_em && $v->fim_em===$registro->fim_em
            && (int)$v->espaco_id===(int)$registro->espaco_id && $espaco && $espaco->situacao==='ativo'
            && \App\Support\PerfisEspaco::compativel($registro->perfil,$espaco,(int)$u->id),403);
        $v->espaco=$espaco;
        abort_if($ajustes->contains(fn($a)=>$a->permissao===self::PERMISSAO && $a->efeito==='retirar'
            && (!$a->valida_ate || $a->valida_ate>=now()->toDateString())),403);
        abort_unless(DB::table('perfil_permissoes')->where('perfil',$v->perfil)->where('permissao',self::PERMISSAO)
            ->where('tipo','padrao')->lockForUpdate()->first(),403);
        abort_unless(in_array($v->perfil,['admin_rede','professor_autonomo'],true) && Permissao::pode($u,self::PERMISSAO),403);
        return $v;
    }
    private function pedido(string $id): object
    {
        $p=DB::table('pedidos_titulares')->where('id',$id)->lockForUpdate()->first();abort_unless($p,404);return $p;
    }
    private function escopo(object $v,object $p): object
    {
        abort_unless(DB::table('clientes')->where('id',$p->cliente_id)->where('situacao','ativo')->lockForUpdate()->first(),404);
        $e=DB::table('espacos')->where('id',$p->espaco_id)->lockForUpdate()->first();
        abort_unless($e && (int)$e->cliente_id===(int)$p->cliente_id && (int)$e->cliente_id===(int)$v->espaco->cliente_id
            && ContextoVinculo::espacoValido($e),404);
        if ($v->perfil==='professor_autonomo') {
            abort_unless($e->tipo==='pessoal' && (int)$e->id===(int)$v->espaco_id && (int)$e->usuario_proprietario_id===(int)$v->usuario_id,404);
        } else {
            abort_unless($e->tipo==='escola',404);
            $escola=DB::table('escolas')->where('id',$e->escola_id)->lockForUpdate()->first();
            $nucleo=$escola ? DB::table('nucleos')->where('id',$escola->nucleo_id)->lockForUpdate()->first() : null;
            abort_unless($nucleo && (int)$nucleo->rede_id===(int)$v->espaco->rede_id,404);
            $pais=DB::table('espacos')->where(fn($q)=>$q->where('nucleo_id',$nucleo->id)->orWhere('rede_id',$nucleo->rede_id))
                ->orderBy('id')->lockForUpdate()->get();
            abort_unless($pais->contains(fn($pai)=>$pai->tipo==='nucleo' && (int)$pai->nucleo_id===(int)$nucleo->id
                && $pai->situacao==='ativo' && (int)$pai->cliente_id===(int)$p->cliente_id)
                && $pais->contains(fn($pai)=>$pai->tipo==='rede' && (int)$pai->rede_id===(int)$nucleo->rede_id
                && $pai->situacao==='ativo' && (int)$pai->cliente_id===(int)$p->cliente_id),404);
            abort_unless(ContextoVinculo::espacoValido($e),404);
        }
        return $e;
    }
    private function sujeito(object $p,object $e): string
    {
        abort_unless($p->aluno_id && $p->sujeito_versao>0,409,'Sujeito não vinculado.');
        $a=DB::table('alunos')->where('id',$p->aluno_id)->where('espaco_id',$p->espaco_id)->lockForUpdate()->first();
        abort_unless($a,404);
        $t=DB::table('turmas')->where('id',$a->turma_id)->where('espaco_id',$p->espaco_id)->lockForUpdate()->first();
        abort_unless($t && (($e->tipo==='pessoal' && $t->escola_id===null)
            || ($e->tipo==='escola' && (int)$t->escola_id===(int)$e->escola_id)),404);
        return DadoProtegido::hash(json_encode([(int)$a->id,(int)$a->turma_id,(int)$a->espaco_id,$a->updated_at],JSON_THROW_ON_ERROR),'exportacao_sujeito');
    }
    private function responsavel(object $p): object
    {
        $a=DB::table('usuario_acessos_internos as a')->join('usuarios as u','u.id','=','a.usuario_id')
            ->where('a.id',$p->responsavel_acesso_id)->where('a.versao_autorizacao',$p->responsavel_versao)
            ->whereIn('a.perfil',['desenvolvedor','suporte'])->where('a.situacao','ativo')->where('u.ativo',true)
            ->where('u.forcar_troca_senha',false)->where('a.inicio_em','<=',now())
            ->where(fn($q)=>$q->whereNull('a.fim_em')->orWhere('a.fim_em','>',now()))->select('a.*')->lockForUpdate()->first();
        abort_unless($a && (!$p->atribuicao_fim_em || Carbon::parse($p->atribuicao_fim_em)->isFuture()),403);
        return $a;
    }
    private function limite(object $p,object $v,object $a): Carbon
    {
        abort_unless($p->tipo==='acesso' && $p->situacao==='em_analise',409,'Protocolo não elegível.');
        $vs=DB::table('pedido_titular_verificacoes')->where('pedido_id',$p->id)->where('ciclo',$p->ciclo_verificacao)
            ->where('acesso_id',$p->responsavel_acesso_id)->where('acesso_versao',$p->responsavel_versao)
            ->where('valida_ate','>',now())->whereIn('tipo',['identidade','autoridade'])->lockForUpdate()->get();
        abort_unless($vs->pluck('tipo')->unique()->count()===2,409,'Conferências pendentes.');
        $datas=$vs->pluck('valida_ate')->all();
        foreach ([$v->fim_em,$a->fim_em,$p->atribuicao_fim_em] as $data) if ($data) $datas[]=$data;
        return collect($datas)->map(fn($d)=>Carbon::parse($d)->utc())->sortBy(fn($d)=>$d->timestamp)->first();
    }
    private function validar(array $dados,array $regras): array
    {
        if (array_diff(array_keys($dados),array_keys($regras))) throw ValidationException::withMessages(['autorizacao'=>'Campos não permitidos.']);
        return Validator::make($dados,$regras)->validate();
    }
    private function recibo(object $v,string $assinatura,string $acao,array $d,?array $resposta=null): ?array
    {
        ksort($d);$hash=DadoProtegido::hash(json_encode($d,JSON_THROW_ON_ERROR),'exportacao_concessao');
        $q=['vinculo_id'=>$v->id,'vinculo_assinatura'=>$assinatura,'acao'=>$acao,'chave'=>$d['chave']];
        if ($resposta!==null) {
            DB::table('pedido_exportacao_recibos')->insert($q+['payload_hash'=>$hash,'resposta'=>json_encode($resposta,JSON_THROW_ON_ERROR),'created_at'=>now()]);
            return $resposta;
        }
        $r=DB::table('pedido_exportacao_recibos')->where($q)->first();
        if (!$r) return null;
        abort_unless(hash_equals($r->payload_hash,$hash),409,'Chave já utilizada com outros dados.');
        return json_decode($r->resposta,true,512,JSON_THROW_ON_ERROR);
    }
    private function evento(Usuario $u,string $id,string $acao,string $motivo): void
    {
        DB::table('pedido_exportacao_eventos')->insert(['autorizacao_id'=>$id,'ator_id'=>$u->id,'acao'=>$acao,
            'motivo'=>Crypt::encryptString($motivo),'created_at'=>now()]);
    }
    public function consultarPedido(Usuario $u,string $id): array
    {
        $this->habilitar();
        return DB::transaction(function()use($u,$id){
            $this->controle();$v=$this->aprovador($u);$p=$this->pedido($id);$e=$this->escopo($v,$p);
            $this->sujeito($p,$e);$a=$this->responsavel($p);$limite=$this->limite($p,$v,$a);
            return ['pedido_id'=>$id,'versao'=>(int)$p->versao,'aluno_id'=>(int)$p->aluno_id,
                'sujeito_versao'=>(int)$p->sujeito_versao,'destinatario_acesso_id'=>(int)$a->id,
                'limite_validade'=>$limite->toIso8601String(),'formato'=>'cadastro_minimo_v1','campos_permitidos'=>['nome','matricula']];
        },3);
    }
    public function conceder(Usuario $u,string $id,array $dados): array
    {
        $this->habilitar();
        $d=$this->validar($dados,['versao'=>'required|integer|min:1','aluno_id'=>'required|integer|min:1','sujeito_versao'=>'required|integer|min:1',
            'destinatario_acesso_id'=>'required|integer|min:1','campos'=>'required|array|min:1|max:2',
            'campos.*'=>'required|string|distinct|in:nome,matricula','valida_ate'=>'required|date',
            'motivo'=>'required|string|min:5|max:500','evidencia_referencia'=>'required|string|min:5|max:500','chave'=>'required|uuid']);
        sort($d['campos']);
        return DB::transaction(function()use($u,$id,$d){
            $this->controle();$v=$this->aprovador($u);$p=$this->pedido($id);$e=$this->escopo($v,$p);
            $assinatura=ContextoVinculo::versaoAcesso($u);
            if ($r=$this->recibo($v,$assinatura,'conceder:'.$id,$d)) return $r;
            abort_unless((int)$p->versao===(int)$d['versao'] && (int)$p->aluno_id===(int)$d['aluno_id']
                && (int)$p->sujeito_versao===(int)$d['sujeito_versao'],409,'Protocolo alterado.');
            $alunoAssinatura=$this->sujeito($p,$e);$a=$this->responsavel($p);
            abort_unless((int)$a->id===(int)$d['destinatario_acesso_id'],422,'Destinatário incompatível.');
            $limite=$this->limite($p,$v,$a);$validade=Carbon::parse($d['valida_ate'])->utc()->startOfSecond();
            abort_unless($validade->isFuture() && $validade->lte($limite),422,'Prazo fora da validade autorizável.');
            abort_if(DB::table('pedido_exportacao_autorizacoes')->where('pedido_id',$id)->where('sujeito_versao',$p->sujeito_versao)
                ->where('ciclo',$p->ciclo_verificacao)->whereNull('revogada_em')->where('valida_ate','>',now())->exists(),409,'Revogue a concessão anterior antes de substituir.');
            $ref=(string)Str::uuid();
            DB::table('pedido_exportacao_autorizacoes')->insert(['id'=>$ref,'pedido_id'=>$id,'aluno_id'=>$p->aluno_id,
                'sujeito_versao'=>$p->sujeito_versao,'ciclo'=>$p->ciclo_verificacao,'aluno_assinatura'=>$alunoAssinatura,
                'aprovador_id'=>$u->id,'vinculo_id'=>$v->id,'vinculo_assinatura'=>$assinatura,
                'destinatario_acesso_id'=>$a->id,'destinatario_versao'=>$a->versao_autorizacao,
                'formato'=>'cadastro_minimo_v1','campos'=>json_encode($d['campos'],JSON_THROW_ON_ERROR),
                'evidencia'=>Crypt::encryptString($d['evidencia_referencia']),'valida_ate'=>$validade->format('Y-m-d H:i:s'),'created_at'=>now()]);
            DB::table('pedidos_titulares')->where('id',$id)->increment('versao',1,['updated_at'=>now()]);
            $this->evento($u,$ref,'conceder',$d['motivo']);
            return $this->recibo($v,$assinatura,'conceder:'.$id,$d,['referencia'=>$ref,'versao'=>1,'pedido_versao'=>$p->versao+1,'valida_ate'=>$validade->toIso8601String()]);
        },3);
    }
    public function revogar(Usuario $u,string $id,array $dados): array
    {
        $this->habilitar();$d=$this->validar($dados,['versao'=>'required|integer|min:1','motivo'=>'required|string|min:5|max:500','chave'=>'required|uuid']);
        return DB::transaction(function()use($u,$id,$d){
            $this->controle();$v=$this->aprovador($u);$ref=DB::table('pedido_exportacao_autorizacoes')->where('id',$id)->first();abort_unless($ref,404);
            $p=$this->pedido($ref->pedido_id);$this->escopo($v,$p);
            $g=DB::table('pedido_exportacao_autorizacoes')->where('id',$id)->lockForUpdate()->first();
            $assinatura=ContextoVinculo::versaoAcesso($u);
            if ($r=$this->recibo($v,$assinatura,'revogar:'.$id,$d)) return $r;
            abort_unless((int)$g->versao===(int)$d['versao'] && $g->revogada_em===null,409);
            DB::table('pedido_exportacao_autorizacoes')->where('id',$id)->update(['revogada_em'=>now(),'versao'=>$g->versao+1]);
            $this->evento($u,$id,'revogar',$d['motivo']);
            return $this->recibo($v,$assinatura,'revogar:'.$id,$d,['referencia'=>$id,'versao'=>$g->versao+1,'revogada'=>true]);
        },3);
    }
    /** Consumidores futuros devem executar a operação protegida no callback, sob os locks. */
    public function comAutorizacao(Usuario $u,string $id,callable $operacao): mixed
    {
        $this->habilitar();
        return DB::transaction(function()use($u,$id,$operacao){
            $this->controle();$interno=ContextoInterno::resolver($u);
            abort_unless(in_array($interno->perfil,['desenvolvedor','suporte'],true),403);
            $ref=DB::table('pedido_exportacao_autorizacoes')->where('id',$id)->first();abort_unless($ref,404);
            $p=$this->pedido($ref->pedido_id);
            $g=DB::table('pedido_exportacao_autorizacoes')->where('id',$id)->lockForUpdate()->first();
            abort_unless((int)$g->destinatario_acesso_id===(int)$interno->id && (int)$g->destinatario_versao===(int)$interno->versao_autorizacao
                && (int)$p->responsavel_acesso_id===(int)$interno->id && (int)$p->responsavel_versao===(int)$interno->versao_autorizacao,404);
            abort_unless($g->revogada_em===null && Carbon::parse($g->valida_ate)->isFuture(),403);
            abort_unless((int)$g->aluno_id===(int)$p->aluno_id && (int)$g->sujeito_versao===(int)$p->sujeito_versao && (int)$g->ciclo===(int)$p->ciclo_verificacao,403);
            $emissor=Usuario::find($g->aprovador_id);abort_unless($emissor,403);$emissor=$emissor->noVinculo($g->vinculo_id);
            $v=$this->aprovador($emissor);$e=$this->escopo($v,$p);$a=$this->responsavel($p);$limite=$this->limite($p,$v,$a);
            abort_unless(hash_equals($g->vinculo_assinatura,ContextoVinculo::versaoAcesso($emissor))
                && hash_equals($g->aluno_assinatura,$this->sujeito($p,$e)),403);
            $campos=json_decode($g->campos,true,512,JSON_THROW_ON_ERROR);
            abort_unless($g->formato==='cadastro_minimo_v1' && is_array($campos) && count($campos)>0 && !array_diff($campos,['nome','matricula']),403);
            $this->evento($u,$id,'uso_validado','Concessão revalidada para operação protegida.');
            return $operacao(['referencia'=>$id,'pedido_id'=>$p->id,'aluno_id'=>(int)$p->aluno_id,'espaco_id'=>(int)$p->espaco_id,
                'cliente_id'=>(int)$p->cliente_id,'formato'=>$g->formato,'campos'=>$campos,
                'valida_ate'=>Carbon::parse($g->valida_ate)->min($limite)->utc()->format('Y-m-d H:i:s')]);
        },3);
    }
}
