<?php

namespace App\Services;

use App\Support\Catalogo\EntradaCatalogo;
use App\Support\Catalogo\LeitorInstituicoes;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Projeção pública mínima; consumidores fixam edições entre validação e leitura. */
class ConsultarCatalogoInstituicoes
{
    public function vigentes(): Builder
    {
        return $this->edicoes($this->idsVigentes());
    }

    private function idsVigentes(): array { return DB::table('catalogo_fontes')->whereNotNull('publicacao_id')->pluck('publicacao_id')->all(); }

    private function edicoes(array $ids): Builder
    {
        return DB::table('catalogo_publicacoes as p')->join('catalogo_entradas as e','e.publicacao_id','=','p.id')
            ->whereIn('p.id',$ids)->where('p.estado','publicada');
    }

    public function buscar(array $filtros, ?array $edicoesFixadas = null): LengthAwarePaginator
    {
        $v = Validator::make($filtros,[
            'uf'=>['sometimes',Rule::in(array_values(LeitorInstituicoes::UFS_IBGE))],
            'municipio_ibge'=>['sometimes','regex:/^[0-9]{7}$/D'],
            'tipo'=>['sometimes',Rule::in(['escola','faculdade','centro_universitario','universidade','instituto_federal','cefet','instituto','outro'])],
            'dependencia'=>['sometimes',Rule::in(['federal','estadual','municipal','privada','especial'])],
            'namespace'=>['required_with:codigo',Rule::in(['inep_escola','inep_ies'])],
            'codigo'=>['sometimes','string','regex:/^[0-9]{1,8}$/D'],
            'busca'=>['sometimes','string','min:2','max:100'],
            'situacao'=>['sometimes',Rule::in(['1','2','3','4','nao_informada','todas'])],
            'page'=>['sometimes','integer','min:1','max:100000'], 'per_page'=>['sometimes','integer','min:1','max:50'],
        ])->validate();
        if (!isset($v['uf']) && !isset($v['municipio_ibge']) && !isset($v['codigo']) && mb_strlen(trim($v['busca']??''))<3) {
            throw ValidationException::withMessages(['busca'=>'Informe localização, código ou pelo menos três caracteres.']);
        }
        if (isset($v['codigo']) && !preg_match($v['namespace'] === 'inep_escola' ? '/^[0-9]{8}$/D' : '/^[1-9][0-9]{0,7}$/D',$v['codigo'])) throw ValidationException::withMessages(['codigo'=>'Código incompatível com a fonte.']);
        $edicoes = $edicoesFixadas ?? $this->idsVigentes();
        if (isset($v['municipio_ibge'])) {
            $municipio = DB::table('catalogo_municipios as m')->join('catalogo_publicacoes as p','p.geografia_id','=','m.geografia_id')
                ->whereIn('p.id',$edicoes)->where('p.estado','publicada')->where('m.codigo_ibge',$v['municipio_ibge'])
                ->when(isset($v['uf']),fn($q)=>$q->where('m.uf',$v['uf']))->exists();
            if (!$municipio) throw ValidationException::withMessages(['municipio_ibge'=>'Município não pertence à UF ou à geografia vigente.']);
        }
        $q = $this->edicoes($edicoes)->join('catalogo_municipios as m',function($j) { $j->on('m.geografia_id','=','e.geografia_id')->on('m.codigo_ibge','=','e.municipio_ibge'); });
        foreach (['uf','municipio_ibge','tipo','dependencia','namespace','codigo'] as $campo) if (isset($v[$campo])) $q->where('e.'.$campo,$v[$campo]);
        $situacao = $v['situacao']??'corrente';
        if ($situacao === 'corrente') $q->where(fn($s)=>$s->where('e.situacao_fonte','1')->orWhereNull('e.situacao_fonte'));
        elseif ($situacao === 'nao_informada') $q->whereNull('e.situacao_fonte');
        elseif ($situacao !== 'todas') $q->where('e.situacao_fonte',$situacao);
        if (isset($v['busca'])) {
            $termo = str_replace(['!','%','_'],['!!','!%','!_'],EntradaCatalogo::normalizar($v['busca']));
            $q->where(fn($s)=>$s->whereRaw("e.nome_normalizado LIKE ? ESCAPE '!'",['%'.$termo.'%'])->orWhereRaw("e.sigla_normalizada LIKE ? ESCAPE '!'",['%'.$termo.'%']));
        }
        return $q->select('e.instituicao_id','e.unidade_id','e.nome','e.sigla','e.namespace','e.codigo','e.tipo','e.natureza','e.dependencia',
            'e.dependencia_fonte','e.situacao_fonte','e.uf','e.municipio_ibge','m.nome as municipio_nome','p.id as edicao_id','p.ano','p.fonte')
            ->orderBy('e.nome_normalizado')->orderBy('e.unidade_id')->paginate((int)($v['per_page']??20),['*'],'page',(int)($v['page']??1));
    }

    /** Sem associação automática e sem dados de pessoas. Operador revisa candidatos. */
    public function conciliacao(): array
    {
        $resultado = [];
        $edicao = $this->vigentes();
        foreach (DB::table('escolas')->select('id','inep','nome','uf','co_municipio')->orderBy('id')->get() as $escola) {
            $c = (clone $edicao)->where('e.namespace','inep_escola')->where('e.codigo',$escola->inep)
                ->select('e.unidade_id','e.nome','e.uf','e.municipio_ibge','e.situacao_fonte','p.id as edicao_id')->first();
            $situacao = !$c ? 'sem_correspondencia' : (!$escola->uf || !$escola->co_municipio ? 'dados_incompletos'
                : ($c->uf !== $escola->uf || $c->municipio_ibge !== (string)$escola->co_municipio ? 'geografia_divergente' : 'candidato_compativel'));
            $resultado[] = ['escola_id'=>$escola->id,'inep'=>$escola->inep,'situacao'=>$situacao,'unidade_candidata'=>$c?->unidade_id,
                'edicao_referencia'=>$c?->edicao_id,
                'nome_divergente'=>$c ? EntradaCatalogo::normalizar($c->nome)!==EntradaCatalogo::normalizar($escola->nome) : null,
                'situacao_fonte'=>$c?->situacao_fonte,'associacao_executada'=>false];
        }
        return $resultado;
    }
}
