<?php
namespace App\Services;
use App\Models\Usuario;
use App\Support\DadoProtegido;
use Illuminate\Support\Facades\{DB,Crypt,Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class SolicitacoesCatalogo
{
    public function listar(Usuario $u,array $f=[],bool $interno=false): array
    {
        $p=app(PoliticaCatalogo::class);$ator=$interno?$p->interno($u):$p->pessoal($u);
        $v=Validator::make($f,['page'=>'sometimes|integer|min:1|max:100000','situacao'=>['sometimes',Rule::in(['enviada','em_analise','aguardando_complementacao','aprovada','indeferida','cancelada'])]])->validate();
        $q=DB::table('catalogo_solicitacoes');if(!$interno) $q->where('usuario_id',$u->id)->where('espaco_id',$ator->espaco_id);
        if(isset($v['situacao']))$q->where('situacao',$v['situacao']);
        $r=$q->orderByDesc('created_at')->orderBy('id')->paginate(20,['*'],'page',(int)($v['page']??1));
        return ['itens'=>array_map(fn($x)=>$this->resumo($x),$r->items()),'paginacao'=>app(CatalogoSelecao::class)->pagina($r)];
    }
    public function detalhe(Usuario $u,string $id,bool $interno=false): array
    {
        $p=app(PoliticaCatalogo::class);$a=$interno?$p->interno($u):$p->pessoal($u);
        $r=$this->pedido($u,$a,strtolower($id),$interno);
        $eventos=DB::table('catalogo_solicitacao_eventos')->where('solicitacao_id',$r->id)->orderBy('versao')->get(['versao','acao','mensagem','ocorrido_em']);
        return $this->resumo($r)+['dados'=>json_decode(Crypt::decryptString($r->dados),true,512,JSON_THROW_ON_ERROR),
            'eventos'=>$eventos->map(fn($e)=>['versao'=>(int)$e->versao,'acao'=>$e->acao,'mensagem'=>Crypt::decryptString($e->mensagem),'ocorrido_em'=>$e->ocorrido_em])->all(),
            'avisos'=>DB::table('catalogo_solicitacao_avisos')->where('solicitacao_id',$r->id)->orderBy('versao')->get(['versao','situacao','created_at'])->all()];
    }
    public function criar(Usuario $u,array $dados): array
    {
        abort_unless(!array_diff(array_keys($dados),['chave','cadastro']),422,'Campos não permitidos.');
        $v=Validator::make($dados,['chave'=>'required|uuid','cadastro'=>'required|array'])->validate();
        app(PoliticaCatalogo::class)->pessoal($u);$cadastro=app(ManutencaoCatalogo::class)->validar($v['cadastro']);$hash=$this->hash($cadastro);$chave=strtolower($v['chave']);
        return DB::transaction(function()use($u,$cadastro,$hash,$chave){
            $p=app(PoliticaCatalogo::class);$a=$p->pessoal($u,true);
            $r=DB::table('catalogo_solicitacoes')->where('usuario_id',$u->id)->where('espaco_id',$a->espaco_id)->where('chave',$chave)->lockForUpdate()->first();
            if($r){abort_unless(hash_equals($r->payload_hash,$hash),409,'A chave já foi usada em outra solicitação.');return $this->resumo($r);}
            $id=(string)Str::uuid();
            $snapshot=$this->snapshot($cadastro);
            DB::table('catalogo_solicitacoes')->insert(['id'=>$id,'usuario_id'=>$u->id,'espaco_id'=>$a->espaco_id,'vinculo_id'=>$a->id,'chave'=>$chave,'payload_hash'=>$hash,'dados'=>Crypt::encryptString(json_encode($snapshot,JSON_THROW_ON_ERROR)),
                'situacao'=>'enviada','versao'=>1,'created_at'=>now(),'updated_at'=>now()]);
            $this->evento($u,$id,1,'enviada','Solicitação recebida pela equipe de suporte.',$hash,'enviada',['cadastro'=>$snapshot]);
            $p->pessoal($u,true);return $this->resumo(DB::table('catalogo_solicitacoes')->find($id));
        },3);
    }
    public function alterar(Usuario $u,string $id,array $d,bool $interno=false): array
    {
        $permitidos=$interno?['versao','acao','mensagem','unidade_id','edicao_id','cadastro','motivo','conferencia','situacao']:['versao','acao','cadastro','conferencia'];
        abort_unless(!array_diff(array_keys($d),$permitidos),422,'Campos não permitidos.');
        $v=Validator::make($d,['versao'=>'required|integer|min:1','acao'=>['required',Rule::in($interno?['analisar','pedir_complemento','indeferir','aprovar_existente','aprovar_nova']:['complementar','cancelar'])],
            'mensagem'=>'nullable|string|max:1000','unidade_id'=>'nullable|uuid','edicao_id'=>'nullable|uuid','cadastro'=>'nullable|array','motivo'=>'nullable|string|min:5|max:1000','conferencia'=>'sometimes|accepted','situacao'=>['nullable',Rule::in(['1','2','3'])]])->validate();
        $v['versao']=(int)$v['versao'];
        foreach(['unidade_id','edicao_id'] as $campo)if(!empty($v[$campo]))$v[$campo]=strtolower($v[$campo]);
        foreach(['mensagem','motivo'] as $campo)if(isset($v[$campo]))$v[$campo]=trim($v[$campo]);
        if(isset($v['conferencia']))$v['conferencia']=true;
        $hash=$this->hash($v);
        return DB::transaction(function()use($u,$id,$v,$hash,$interno){
            $p=app(PoliticaCatalogo::class);
            if($interno){app(ManutencaoCatalogo::class)->controle();$p->fontes();$a=$p->interno($u,true);}else $a=$p->pessoal($u,true);
            $r=$this->pedido($u,$a,strtolower($id),$interno,true);
            if((int)$r->versao!==(int)$v['versao']) {
                $e=DB::table('catalogo_solicitacao_eventos')->where('solicitacao_id',$r->id)->where('versao',$r->versao)->first();
                if((int)$r->versao===(int)$v['versao']+1 && $e && hash_equals($e->payload_hash,$hash) && (int)$e->ator_id===(int)$u->id) return $this->resumo($r);
                abort(409,'A solicitação mudou. Atualize antes de continuar.');
            }
            abort_unless(!in_array($r->situacao,['aprovada','indeferida','cancelada'],true),409,'Solicitação já encerrada.');
            $update=[];$mensagem='';
            switch($v['acao']) {
                case 'cancelar':
                    if(empty($v['conferencia']))throw \Illuminate\Validation\ValidationException::withMessages(['conferencia'=>'Confirme que deseja cancelar este pedido.']);
                    $estado='cancelada';$mensagem='Solicitação cancelada pelo solicitante.';break;
                case 'complementar':
                    abort_unless($r->situacao==='aguardando_complementacao',409,'Não há complementação pendente.');
                    $cadastro=app(ManutencaoCatalogo::class)->validar($v['cadastro']??[]);
                    $snapshot=$this->snapshot($cadastro);$v['cadastro']=$snapshot;
                    $update['dados']=Crypt::encryptString(json_encode($snapshot,JSON_THROW_ON_ERROR));$estado='em_analise';$mensagem='Complementação recebida.';break;
                case 'analisar':abort_unless($r->situacao==='enviada',409,'Solicitação já está em análise.');$estado='em_analise';$mensagem='Solicitação em análise pela equipe de suporte.';break;
                case 'pedir_complemento':case 'indeferir':
                    abort_unless($r->situacao==='em_analise',409,'Inicie a análise antes de decidir.');
                    if($v['acao']==='indeferir' && empty($v['conferencia']))throw \Illuminate\Validation\ValidationException::withMessages(['conferencia'=>'Confirme o encerramento do pedido com este motivo.']);
                    abort_unless(mb_strlen(trim($v['mensagem']??''))>=5,422,'Explique o motivo ao solicitante.');
                    $estado=$v['acao']==='indeferir'?'indeferida':'aguardando_complementacao';$mensagem=trim($v['mensagem']);break;
                case 'aprovar_existente':case 'aprovar_nova':
                    abort_unless($r->situacao==='em_analise',409,'Inicie a análise antes de decidir.');
                    abort_unless(!empty($v['motivo']) && !empty($v['conferencia']),422,'Registre o motivo e confirme a conferência.');
                    if($v['acao']==='aprovar_existente') {
                        abort_unless(!empty($v['unidade_id']) && !empty($v['edicao_id']),422,'Confira a instituição e sua edição.');
                        $e=app(CatalogoSelecao::class)->entrada(strtolower($v['unidade_id']));
                        abort_unless($e->edicao_id===strtolower($v['edicao_id']),409,'O catálogo mudou. Confira novamente.');
                        abort_unless(app(CatalogoSelecao::class)->elegivel($e),422,'Referência indisponível para nova seleção.');
                        $res=['unidade_id'=>$e->unidade_id,'edicao_id'=>$e->edicao_id];
                    } else {
                        abort_unless(($v['situacao']??'')==='1',422,'A aprovação exige referência ativa.');
                        $res=app(ManutencaoCatalogo::class)->gravar($u,$v);
                    }
                    $update+=['unidade_id'=>$res['unidade_id'],'publicacao_id'=>$res['edicao_id']];$estado='aprovada';
                    $mensagem='Cadastro disponível. Confira a instituição no catálogo antes de adicioná-la às suas referências.';break;
            }
            $versao=(int)$r->versao+1;
            DB::table('catalogo_solicitacoes')->where('id',$r->id)->update($update+['situacao'=>$estado,'versao'=>$versao,'updated_at'=>now()]);
            $this->evento($u,$r->id,$versao,$v['acao'],$mensagem,$hash,$estado,$v+['situacao_anterior'=>$r->situacao]);
            $interno?$p->interno($u,true):$p->pessoal($u,true);
            return $this->resumo(DB::table('catalogo_solicitacoes')->find($r->id));
        },3);
    }
    private function pedido(Usuario $u,object $a,string $id,bool $interno,bool $lock=false): object
    {
        $q=DB::table('catalogo_solicitacoes')->where('id',$id);if(!$interno)$q->where('usuario_id',$u->id)->where('espaco_id',$a->espaco_id);
        $r=$lock?$q->lockForUpdate()->first():$q->first();abort_unless($r,404,'Solicitação não encontrada.');return $r;
    }
    private function resumo(object $r): array {
        $d=json_decode(Crypt::decryptString($r->dados),true,512,JSON_THROW_ON_ERROR);
        return ['id'=>$r->id,'situacao'=>$r->situacao,'versao'=>(int)$r->versao,'nome_instituicao'=>$d['nome'],'municipio_nome'=>$d['municipio_nome']??null,'uf'=>$d['uf'],'unidade_id'=>$r->unidade_id,'edicao_id'=>$r->publicacao_id,'criado_em'=>$r->created_at,'atualizado_em'=>$r->updated_at];
    }
    private function snapshot(array $cadastro): array {
        $geo=app(CatalogoSelecao::class)->edicoes()[0]->geografia_id;
        $municipio=DB::table('catalogo_municipios')->where('geografia_id',$geo)->where('codigo_ibge',$cadastro['municipio_ibge'])->where('uf',$cadastro['uf'])->first();
        return $cadastro+['municipio_nome'=>$municipio?->nome,'geografia_id'=>$geo];
    }
    private function hash(array $d): string { ksort($d);foreach($d as &$v)if(is_array($v))ksort($v);return DadoProtegido::hash(json_encode($d,JSON_THROW_ON_ERROR),'catalogo_solicitacao'); }
    private function evento(Usuario $u,string $id,int $versao,string $acao,string $mensagem,string $hash,string $situacao,array $detalhes): void
    {
        $detalhes['acesso_versao']=$u->acessoInternoContextoId()!==null?$u->acessoInternoContextoVersao():null;
        DB::table('catalogo_solicitacao_eventos')->insert(['solicitacao_id'=>$id,'versao'=>$versao,'ator_id'=>$u->id,'vinculo_id'=>$u->vinculoContextoId(),'acesso_id'=>$u->acessoInternoContextoId(),'acao'=>$acao,'mensagem'=>Crypt::encryptString($mensagem),'detalhes'=>Crypt::encryptString(json_encode($detalhes,JSON_THROW_ON_ERROR)),'payload_hash'=>$hash,'ocorrido_em'=>now()]);
        DB::table('catalogo_solicitacao_avisos')->insert(['solicitacao_id'=>$id,'versao'=>$versao,'situacao'=>$situacao,'created_at'=>now()]);
    }
}
