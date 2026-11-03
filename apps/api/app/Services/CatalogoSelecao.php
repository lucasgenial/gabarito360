<?php
namespace App\Services;

use App\Support\Catalogo\EntradaCatalogo;
use App\Support\Catalogo\LeitorInstituicoes;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\Rule;

/** Apenas projeções públicas das edições capturadas; sem consultas escolares. */
class CatalogoSelecao
{
    public function edicoes(): array
    {
        $r=DB::table('catalogo_fontes as f')->join('catalogo_publicacoes as p','p.id','=','f.publicacao_id')
            ->where('p.estado','publicada')->orderByRaw("CASE f.nome WHEN 'basica' THEN 0 WHEN 'superior' THEN 1 ELSE 2 END")->get(['p.id','p.fonte','p.ano','p.total','p.geografia_id'])->all();
        abort_unless($r,503,'O catálogo está temporariamente indisponível. Tente novamente.');
        return $r;
    }

    public function ufs(): array
    {
        $geo=$this->edicoes()[0]->geografia_id;
        return ['itens'=>DB::table('catalogo_ufs')->where('geografia_id',$geo)->orderBy('nome')->get(['sigla','nome','codigo_ibge'])->all(),'geografia_id'=>$geo];
    }

    public function municipios(array $f): array
    {
        $v=Validator::make($f,['uf'=>['required',Rule::in(array_values(LeitorInstituicoes::UFS_IBGE))],
            'busca'=>'sometimes|string|max:100','codigo_ibge'=>'sometimes|regex:/^[0-9]{7}$/D',
            'page'=>'sometimes|integer|min:1|max:10000'])->validate();
        $geo=$this->edicoes()[0]->geografia_id;
        $q=DB::table('catalogo_municipios')->where('geografia_id',$geo)->where('uf',$v['uf']);
        if(isset($v['codigo_ibge'])) $q->where('codigo_ibge',$v['codigo_ibge']);
        if(!empty($v['busca'])) {
            $termo=str_replace(['!','%','_'],['!!','!%','!_'],EntradaCatalogo::normalizar($v['busca']));
            $q->whereRaw("nome_normalizado LIKE ? ESCAPE '!'",['%'.$termo.'%']);
        }
        $p=$q->orderBy('nome_normalizado')->orderBy('codigo_ibge')->paginate(50,['codigo_ibge','nome','uf'],'page',(int)($v['page']??1));
        return ['itens'=>$p->items(),'paginacao'=>$this->pagina($p),'geografia_id'=>$geo];
    }

    public function buscar(array $f): array
    {
        $edicoes=$this->edicoes();
        $p=app(ConsultarCatalogoInstituicoes::class)->buscar($f,array_column($edicoes,'id'));
        return ['itens'=>array_map(fn($e)=>$this->publica($e),$p->items()),'paginacao'=>$this->pagina($p),
            'edicoes'=>array_map(fn($e)=>['id'=>$e->id,'fonte'=>$e->fonte,'ano'=>$e->ano,'total'=>$e->total],$edicoes),
            'cobertura'=>'Escolas de referência e sedes administrativas de IES nas fontes publicadas. Não representa todos os campi ou polos.'];
    }

    public function entrada(string $unidade): object
    {
        $unidade=strtolower($unidade);
        $ids=array_column($this->edicoes(),'id');
        $e=app(ConsultarCatalogoInstituicoes::class)->vigentes()
            ->join('catalogo_municipios as m',fn($j)=>$j->on('m.geografia_id','=','e.geografia_id')->on('m.codigo_ibge','=','e.municipio_ibge'))
            ->whereIn('p.id',$ids)->where('e.unidade_id',$unidade)
            ->select('e.*','m.nome as municipio_nome','p.id as edicao_id','p.fonte','p.ano')->first();
        abort_unless($e,404,'Referência não encontrada na edição corrente.');
        return $e;
    }

    public function publica(object $e): array
    {
        $r=[];
        foreach(['unidade_id','instituicao_id','nome','sigla','namespace','codigo','tipo','natureza','dependencia','situacao_fonte','uf','municipio_ibge','municipio_nome','edicao_id','ano','fonte'] as $k) $r[$k]=$e->$k;
        $r['elegivel']=$this->elegivel($e);
        $r['situacao_rotulo']=$e->fonte==='manual' ? match($e->situacao_fonte){'1'=>'Ativa no cadastro da plataforma','2'=>'Paralisada no cadastro da plataforma',default=>'Inativa no cadastro da plataforma'} : match($e->situacao_fonte){'1'=>'Em atividade na fonte','2'=>'Paralisada','3'=>'Extinta','4'=>'Extinta em anos anteriores',default=>'Situação não informada na fonte'};
        return $r;
    }

    public function elegivel(object $e): bool { return $e->situacao_fonte==='1' || ($e->namespace==='inep_ies' && $e->situacao_fonte===null); }

    public function pagina($p): array { return ['atual'=>$p->currentPage(),'ultima'=>$p->lastPage(),'por_pagina'=>$p->perPage(),'total'=>$p->total()]; }
}
