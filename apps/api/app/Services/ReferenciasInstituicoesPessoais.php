<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoVinculo,Permissao};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferenciasInstituicoesPessoais
{
    public function contexto(Usuario $u, bool $gravar=false): object
    {
        abort_unless(config('catalogo.interface_ativa')===true,503,'Catálogo temporariamente indisponível.');
        abort_unless(ContextoVinculo::presente($u),403);
        if($gravar) {
            abort_unless(DB::table('usuarios')->where('id',$u->id)->where('ativo',true)->where('forcar_troca_senha',false)->lockForUpdate()->first(),403);
            $registro=DB::table('usuario_vinculos')->where('id',$u->vinculoContextoId())->where('usuario_id',$u->id)
                ->where('situacao','ativo')->where('inicio_em','<=',now())->where(fn($q)=>$q->whereNull('fim_em')->orWhere('fim_em','>',now()))->lockForUpdate()->first();
            abort_unless($registro,403);
            $e=DB::table('espacos')->where('id',$registro->espaco_id)->lockForUpdate()->first();
            abort_unless($e && $e->tipo==='pessoal' && $e->situacao==='ativo' && (int)$e->usuario_proprietario_id===(int)$u->id,403);
            abort_unless(DB::table('clientes')->where('id',$e->cliente_id)->where('situacao','ativo')->lockForUpdate()->first(),403);
            DB::table('vinculo_permissoes')->where('vinculo_id',$registro->id)->lockForUpdate()->get();
            DB::table('perfil_permissoes')->where('perfil',$registro->perfil)->whereIn('permissao',['catalogo.consultar','catalogo.referencia_pessoal.gerir'])->lockForUpdate()->get();
        }
        $v=ContextoVinculo::resolver($u);
        abort_unless($v->perfil==='professor_autonomo' && $v->espaco->tipo==='pessoal' && (int)$v->espaco->usuario_proprietario_id===(int)$u->id
            && Permissao::pode($u,'catalogo.consultar') && (!$gravar || Permissao::pode($u,'catalogo.referencia_pessoal.gerir')),403);
        return $v;
    }

    public function listar(Usuario $u, int $page=1): array
    {
        $v=$this->contexto($u);
        $p=DB::table('referencias_instituicoes_pessoais')->where('espaco_id',$v->espaco_id)->where('usuario_id',$u->id)
            ->orderByDesc('created_at')->orderBy('id')->paginate(20,['*'],'page',$page);
        // O catálogo pode estar sem edição corrente; o histórico pessoal continua legível.
        $vigentes=DB::table('catalogo_fontes')->whereNotNull('publicacao_id')->pluck('publicacao_id')->all();
        $atuais=DB::table('catalogo_entradas')->whereIn('publicacao_id',$vigentes)->whereIn('unidade_id',array_column($p->items(),'unidade_id'))->get()->keyBy('unidade_id');
        $entradas=DB::table('catalogo_entradas as e')->join('catalogo_publicacoes as p','p.id','=','e.publicacao_id')
            ->join('catalogo_municipios as m',fn($j)=>$j->on('m.geografia_id','=','e.geografia_id')->on('m.codigo_ibge','=','e.municipio_ibge'))
            ->whereIn('e.id',array_column($p->items(),'entrada_id'))->select('e.*','p.id as edicao_id','p.fonte','p.ano','m.nome as municipio_nome')->get()->keyBy('id');
        return ['itens'=>array_map(function($r)use($entradas,$atuais){
            $e=$entradas[$r->entrada_id];$atual=$atuais[$r->unidade_id]??null;
            return $this->resumo($r)+['instituicao'=>app(CatalogoSelecao::class)->publica($e),
                'edicao_corrente'=>$atual && $atual->publicacao_id===$r->publicacao_id,
                'disponivel_na_edicao_corrente'=>$atual!==null,'elegivel_atualmente'=>$atual ? app(CatalogoSelecao::class)->elegivel($atual) : false];
        },$p->items()),'paginacao'=>app(CatalogoSelecao::class)->pagina($p)];
    }

    public function selecionar(Usuario $u,string $unidade,string $edicao): array
    {
        $p=DB::table('catalogo_publicacoes')->where('id',$edicao)->where('estado','publicada')->first();
        abort_unless($p,409,'A edição mudou. Busque e confira a instituição novamente.');
        return DB::transaction(function()use($u,$unidade,$edicao,$p){
            $f=DB::table('catalogo_fontes')->where('nome',$p->fonte)->sharedLock()->first();
            abort_unless($f && $f->publicacao_id===$edicao,409,'A edição mudou. Busque e confira a instituição novamente.');
            $v=$this->contexto($u,true);
            $e=DB::table('catalogo_entradas')->where('unidade_id',$unidade)->where('publicacao_id',$edicao)->first();
            abort_unless($e,404,'Referência não encontrada.');
            abort_unless(app(CatalogoSelecao::class)->elegivel($e),422,'A situação da referência não permite uma nova seleção.');
            $q=DB::table('referencias_instituicoes_pessoais')->where('espaco_id',$v->espaco_id)->where('unidade_id',$unidade);
            $r=(clone $q)->lockForUpdate()->first();
            if($r && $r->situacao==='ativa' && $r->publicacao_id===$edicao) return $this->resumo($r);
            $dados=['entrada_id'=>$e->id,'publicacao_id'=>$edicao,'situacao'=>'ativa','versao'=>($r->versao??0)+1,'updated_at'=>now()];
            $id=$r->id??(string)Str::uuid();
            if($r) $q->update($dados);
            else DB::table('referencias_instituicoes_pessoais')->insert($dados+['id'=>$id,'espaco_id'=>$v->espaco_id,'usuario_id'=>$u->id,'vinculo_id'=>$v->id,'unidade_id'=>$unidade,'created_at'=>now()]);
            $this->evento($id,$u,$v,$r ? ($r->situacao==='arquivada'?'reativada':'reconfirmada'):'adicionada',$dados['versao'],$r?->publicacao_id,$edicao);
            $this->contexto($u,true);
            return $this->resumo((object)($dados+['id'=>$id]));
        },3);
    }

    public function arquivar(Usuario $u,string $id,int $versao): array
    {
        return DB::transaction(function()use($u,$id,$versao){
            $v=$this->contexto($u,true);
            $q=DB::table('referencias_instituicoes_pessoais')->where('id',$id)->where('espaco_id',$v->espaco_id)->where('usuario_id',$u->id);
            $r=(clone $q)->lockForUpdate()->first();abort_unless($r,404);
            if($r->situacao==='arquivada') return $this->resumo($r);
            abort_unless((int)$r->versao===$versao,409,'A referência mudou. Atualize a página.');
            $q->update(['situacao'=>'arquivada','versao'=>$versao+1,'updated_at'=>now()]);
            $this->evento($id,$u,$v,'arquivada',$versao+1,$r->publicacao_id,$r->publicacao_id);
            $this->contexto($u,true);$r->situacao='arquivada';$r->versao=$versao+1;
            return $this->resumo($r);
        },3);
    }

    private function resumo(object $r): array { return ['id'=>$r->id,'situacao'=>$r->situacao,'versao'=>(int)$r->versao,'edicao_id'=>$r->publicacao_id]; }
    private function evento(string $id,Usuario $u,object $v,string $acao,int $versao,?string $antes,string $depois): void
    {
        DB::table('catalogo_referencia_eventos')->insert(['referencia_id'=>$id,'ator_id'=>$u->id,'vinculo_id'=>$v->id,'acao'=>$acao,
            'versao'=>$versao,'anterior_id'=>$antes,'publicacao_id'=>$depois,'ocorrido_em'=>now()]);
    }
}
