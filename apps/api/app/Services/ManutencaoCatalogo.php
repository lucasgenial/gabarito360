<?php
namespace App\Services;
use App\Models\Usuario;
use App\Support\{ContextoVinculo,ContextoInterno,Catalogo\EntradaCatalogo};
use Illuminate\Support\Facades\{DB,Crypt,Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class ManutencaoCatalogo
{
    public const TIPOS=['escola','faculdade','centro_universitario','universidade','instituto_federal','cefet','instituto','outro'];
    public function validar(array $d,bool $oficial=false): array
    {
        $dependencias=['federal','estadual','municipal','privada','especial'];
        if(!$oficial){$dependencias[]='nao_informada';$d['dependencia']=$d['dependencia']??'nao_informada';}
        $regras=['nome'=>'required|string|max:200','sigla'=>'nullable|string|max:40','tipo'=>['required',Rule::in(self::TIPOS)],
            'uf'=>'required|string|size:2','municipio_ibge'=>'required|regex:/^[0-9]{7}$/D','dependencia'=>['required',Rule::in($dependencias)],
            'namespace'=>['nullable',Rule::in(['inep_escola','inep_ies'])],'codigo'=>'nullable|string|max:8','site'=>'nullable|url:https|max:500','observacao'=>'nullable|string|max:2000'];
        abort_unless(!array_diff(array_keys($d),array_keys($regras)),422,'Campos não permitidos.');
        foreach($d as $k=>$v) if(is_string($v)) $d[$k]=trim($v);
        $v=Validator::make($d,$regras,['required'=>'Informe :attribute.','string'=>':attribute deve ser um texto.','max'=>':attribute ultrapassa o limite de :max caracteres.','in'=>'Selecione uma opção válida para :attribute.','size'=>'Confira :attribute.','regex'=>'Confira o formato de :attribute.','url'=>'Informe um endereço HTTPS válido para :attribute.'],
            ['nome'=>'o nome da instituição','tipo'=>'o tipo da instituição','uf'=>'a UF','municipio_ibge'=>'o município','dependencia'=>'a dependência administrativa','site'=>'o site público','sigla'=>'a sigla','namespace'=>'a fonte do código','codigo'=>'o código'])->validate();
        foreach(['sigla','namespace','codigo','site','observacao'] as $campo) $v[$campo]=($v[$campo]??'')===''?null:$v[$campo];
        if(!empty($v['namespace']) !== !empty($v['codigo']))throw \Illuminate\Validation\ValidationException::withMessages(['codigo'=>'Informe a fonte e o código juntos, ou deixe ambos sem preenchimento.']);
        if(!empty($v['codigo'])) {
            if(!preg_match($v['namespace']==='inep_escola'?'/^[0-9]{8}$/D':'/^[1-9][0-9]{0,7}$/D',$v['codigo']))throw \Illuminate\Validation\ValidationException::withMessages(['codigo'=>'O código não corresponde ao formato da fonte selecionada.']);
            if(($v['namespace']==='inep_escola')!==($v['tipo']==='escola'))throw \Illuminate\Validation\ValidationException::withMessages(['namespace'=>'A fonte do código não corresponde ao tipo de instituição.']);
        }
        if($oficial && !empty($v['codigo']) && empty($v['site']))throw \Illuminate\Validation\ValidationException::withMessages(['site'=>'Informe o endereço público usado para conferir o código.']);
        $geo=app(CatalogoSelecao::class)->edicoes()[0]->geografia_id;
        if(!DB::table('catalogo_municipios')->where('geografia_id',$geo)->where('uf',$v['uf'])->where('codigo_ibge',$v['municipio_ibge'])->exists())throw \Illuminate\Validation\ValidationException::withMessages(['municipio_ibge'=>'Selecione um município da UF informada.']);
        return $v;
    }
    public function controle(): void { abort_unless(DB::table('acessos_internos_controle')->where('id',1)->lockForUpdate()->first(),503); }
    public function gravar(Usuario $u,array $dados,?string $unidade=null,?string $edicao=null,bool $interno=true): array
    {
        return DB::transaction(function()use($u,$dados,$unidade,$edicao,$interno){
            $p=app(PoliticaCatalogo::class);
            if($interno) $this->controle();$p->fontes();
            $ator=$interno?$p->interno($u,true):$p->gestor($u,$unidade,true);
            if($interno)abort_unless(ContextoInterno::pode($u,'catalogo.manter_global'),403);
            abort_unless($interno || $unidade!==null,403);
            $base=$unidade?app(CatalogoSelecao::class)->entrada(strtolower($unidade)):null;
            $cadastro=$dados['cadastro']??[];
            if($base?->namespace==='plataforma') {
                abort_unless(($cadastro['namespace']??'plataforma')==='plataforma' && ($cadastro['codigo']??$base->codigo)===$base->codigo,422,'A manutenção não pode trocar a identidade da unidade.');
                unset($cadastro['namespace'],$cadastro['codigo']);
            }
            $d=$this->validar($cadastro,true);
            $motivo=Validator::make($dados,['motivo'=>'required|string|min:5|max:1000','conferencia'=>'required|accepted','situacao'=>['required',Rule::in(['1','2','3'])]])->validate();
            if($base) {
                abort_unless($edicao && $base->edicao_id===strtolower($edicao),409,'O cadastro mudou. Confira novamente.');
                abort_unless(($d['namespace']??'plataforma')===$base->namespace && ($d['codigo']??$base->codigo)===$base->codigo && $d['tipo']===$base->tipo,422,'A manutenção não pode trocar a identidade ou o tipo da unidade.');
            }
            $payload=['fonte'=>'manual','namespace'=>$base?->namespace??($d['namespace']??'plataforma'),'codigo'=>$base?->codigo??($d['codigo']??(string)Str::ulid()),
                'natureza_localizacao'=>$base?->natureza??($d['tipo']==='escola'?'escola':'sede_administrativa'),
                'nome'=>$d['nome'],'sigla'=>$d['sigla']??null,'tipo'=>$d['tipo'],'uf'=>$d['uf'],'municipio_ibge'=>$d['municipio_ibge'],
                'dependencia_fonte'=>(string)array_search($d['dependencia'],['1'=>'federal','2'=>'estadual','3'=>'municipal','4'=>'privada','7'=>'especial'],true),'situacao_fonte'=>$motivo['situacao']];
            $identidade=EntradaCatalogo::projetar((object)['id'=>0,'ordem'=>0,'payload'=>json_encode($payload)],(object)['id'=>'','importacao_id'=>'','geografia_id'=>'']);
            if($base) abort_unless($identidade['unidade_id']===$base->unidade_id,422,'Identidade divergente.');
            else abort_unless(!DB::table('catalogo_unidades')->where('id',$identidade['unidade_id'])->exists(),409,'A referência já existe. Localize e confira o cadastro existente.');
            $res=$this->publicar($payload,$identidade['unidade_id']);
            $this->evento($u,$ator,$res['unidade_id'],$res['edicao_id'],'cadastro_revisado',['motivo'=>$motivo['motivo'],'site'=>$d['site']??null,'base_edicao'=>$base?->edicao_id],$interno?null:($ator->escola_autorizada_id??null));
            $interno?$p->interno($u,true):$p->gestor($u,$unidade,true);
            return $res;
        },3);
    }
    private function publicar(array $novo,string $unidade): array
    {
        $anterior=DB::table('catalogo_fontes')->where('nome','manual')->value('publicacao_id');
        $lista=[];
        if($anterior) foreach(DB::table('catalogo_entradas as e')->join('catalogo_observacoes as o','o.id','=','e.observacao_id')->where('e.publicacao_id',$anterior)->get(['e.unidade_id','o.payload']) as $r) $lista[$r->unidade_id]=json_decode($r->payload,true,512,JSON_THROW_ON_ERROR);
        $lista[$unidade]=$novo;ksort($lista);
        abort_unless(count($lista)<=1000,503,'O catálogo manual requer preparação em lotes pela equipe técnica.');
        $geo=app(CatalogoSelecao::class)->edicoes()[0]->geografia_id;
        foreach($lista as $d) abort_unless(DB::table('catalogo_municipios')->where('geografia_id',$geo)->where('uf',$d['uf'])->where('codigo_ibge',$d['municipio_ibge'])->exists(),409,'Há referência manual cuja geografia precisa ser revista.');
        $json=EntradaCatalogo::serializar($lista);$hash=hash('sha256',$json);$iid=(string)Str::uuid();$pid=(string)Str::uuid();$n=count($lista);
        DB::table('catalogo_importacoes')->insert(['id'=>$iid,'chave_ingestao'=>hash('sha256',$iid.$hash),'geografia_id'=>$geo,'fonte'=>'manual','ano'=>now()->year,'schema_versao'=>'cadastro-plataforma-v1','arquivo_sha256'=>$hash,
            'manifesto'=>json_encode(['origem'=>'operador','snapshot_sha256'=>$hash,'sem_arquivo_oficial'=>true]),'estado'=>'validando','total_esperado'=>$n,'cursor'=>0,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('catalogo_publicacoes')->insert(['id'=>$pid,'importacao_id'=>$iid,'geografia_id'=>$geo,'fonte'=>'manual','ano'=>now()->year,'estado'=>'preparando','total'=>$n,'cursor'=>0,'anterior_id'=>$anterior,'created_at'=>now(),'updated_at'=>now()]);
        $digest=hash_init('sha256');$ordem=0;
        foreach($lista as $d) {
            $texto=EntradaCatalogo::serializar($d);$ordem++;
            $oid=DB::table('catalogo_observacoes')->insertGetId(['importacao_id'=>$iid,'ordem'=>$ordem,'chave_fonte'=>$d['namespace'].'/'.$d['codigo'],'payload'=>$texto,'payload_sha256'=>hash('sha256',$texto),'created_at'=>now()]);
            $e=EntradaCatalogo::projetar((object)['id'=>$oid,'ordem'=>$ordem,'payload'=>$texto],(object)['id'=>$pid,'importacao_id'=>$iid,'geografia_id'=>$geo]);
            DB::table('catalogo_instituicoes')->insertOrIgnore(['id'=>$e['instituicao_id'],'created_at'=>now()]);
            DB::table('catalogo_identificadores')->insertOrIgnore(['namespace'=>$e['namespace'],'codigo'=>$e['codigo'],'instituicao_id'=>$e['instituicao_id']]);
            DB::table('catalogo_unidades')->insertOrIgnore(['id'=>$e['unidade_id'],'instituicao_id'=>$e['instituicao_id'],'chave_unidade'=>'principal','natureza'=>$e['natureza']]);
            $linha=EntradaCatalogo::serializar($e);DB::table('catalogo_entradas')->insert($e+['projecao_sha256'=>hash('sha256',$linha)]);hash_update($digest,$linha."\n");
        }
        $digest=hash_final($digest);
        DB::table('catalogo_importacoes')->where('id',$iid)->update(['estado'=>'validada','cursor'=>$n,'validado_em'=>now(),'updated_at'=>now()]);
        DB::table('catalogo_publicacoes')->where('id',$pid)->update(['estado'=>'publicada','cursor'=>$n,'projecao_sha256'=>$digest,'publicado_em'=>now(),'updated_at'=>now()]);
        DB::table('catalogo_publicacao_eventos')->insert(['publicacao_id'=>$pid,'anterior_id'=>$anterior,'acao'=>'publicada','motivo'=>'Cadastro da plataforma auditado','projecao_sha256'=>$digest,'ocorrido_em'=>now()]);
        DB::table('catalogo_fontes')->where('nome','manual')->update(['publicacao_id'=>$pid]);
        return ['unidade_id'=>$unidade,'edicao_id'=>$pid];
    }
    public function unidadesGestao(Usuario $u): array
    {
        app(PoliticaCatalogo::class)->gestor($u);
        $ids=DB::table('catalogo_gestao_autoridades')->where('situacao','ativa')->whereIn('escola_id',ContextoVinculo::escolas($u))->distinct()->pluck('unidade_id');
        return $ids->map(fn($id)=>app(CatalogoSelecao::class)->publica(app(CatalogoSelecao::class)->entrada($id)))->all();
    }
    public function autoridade(Usuario $u,array $d): array
    {
        $v=Validator::make($d,['escola_id'=>'required|integer|min:1','unidade_id'=>'required|uuid','versao'=>'required|integer|min:0','situacao'=>['required',Rule::in(['ativa','revogada'])],'motivo'=>'required|string|min:5|max:1000','site'=>'required|url:https|max:500','conferencia'=>'required|accepted'])->validate();
        abort_unless(!array_diff(array_keys($d),array_keys($v)),422);
        return DB::transaction(function()use($u,$v){
            $this->controle();$p=app(PoliticaCatalogo::class);$p->fontes();$a=$p->interno($u,true);
            $e=app(CatalogoSelecao::class)->entrada(strtolower($v['unidade_id']));
            $escola=DB::table('escolas')->where('id',$v['escola_id'])->lockForUpdate()->first();abort_unless($escola,404);
            abort_unless(DB::table('espacos')->where('tipo','escola')->where('escola_id',$escola->id)->where('situacao','ativo')->exists(),422,'A escola precisa ter espaço institucional válido.');
            $q=DB::table('catalogo_gestao_autoridades')->where('escola_id',$escola->id)->where('unidade_id',$e->unidade_id);$r=(clone $q)->lockForUpdate()->first();
            abort_unless((int)($r->versao??0)===(int)$v['versao'],409,'A autoridade mudou. Confira novamente.');
            $dados=['situacao'=>$v['situacao'],'versao'=>$v['versao']+1];
            if($r) $q->update($dados);else DB::table('catalogo_gestao_autoridades')->insert($dados+['escola_id'=>$escola->id,'unidade_id'=>$e->unidade_id]);
            $this->evento($u,$a,$e->unidade_id,null,'autoridade_'.$v['situacao'],['motivo'=>$v['motivo'],'site'=>$v['site'],'versao'=>$dados['versao']],$escola->id);
            $p->interno($u,true);return $dados;
        },3);
    }
    private function evento(Usuario $u,object $ator,string $unidade,?string $edicao,string $acao,array $evidencia,?int $escola): void
    {
        $evidencia['acesso_versao']=$ator->versao_autorizacao??null;
        DB::table('catalogo_manutencao_eventos')->insert(['unidade_id'=>$unidade,'publicacao_id'=>$edicao,'ator_id'=>$u->id,'vinculo_id'=>$u->vinculoContextoId(),'acesso_id'=>$u->acessoInternoContextoId(),'escola_id'=>$escola,'acao'=>$acao,'evidencia'=>Crypt::encryptString(json_encode($evidencia,JSON_THROW_ON_ERROR)),'ocorrido_em'=>now()]);
    }
}
