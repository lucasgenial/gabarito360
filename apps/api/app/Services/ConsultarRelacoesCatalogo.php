<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Metadados públicos. Protocolos privados e decisões de suporte pertencem ao CI1-E. */
class ConsultarRelacoesCatalogo
{
    private function vigente(): ?object
    {
        return DB::table('catalogo_relacao_vigencia as v')->join('catalogo_relacao_conjuntos as c','c.id','=','v.conjunto_id')
            ->join('catalogo_fontes as b',fn($j)=>$j->on('b.publicacao_id','=','c.basica_id')->where('b.nome','basica'))
            ->join('catalogo_fontes as s',fn($j)=>$j->on('s.publicacao_id','=','c.superior_id')->where('s.nome','superior'))
            ->where('v.nome','nacional')->where('c.estado','publicado')->select('c.*')->first();
    }

    private function base(object $c): Builder
    {
        return DB::table('catalogo_relacoes as r')->join('catalogo_entradas as o','o.id','=','r.origem_entrada_id')
            ->leftJoin('catalogo_entradas as a','a.id','=','r.alvo_entrada_id')->where('r.conjunto_id',$c->id);
    }

    public function paraUnidade(string $unidade, ?string $edicaoBasica = null, int $page = 1, int $perPage = 20): array
    {
        $this->limites($page,$perPage); $c = $this->vigente();
        if (!$c || ($edicaoBasica !== null && $edicaoBasica !== $c->basica_id)) return ['situacao'=>'edicao_sem_relacoes','dados'=>null];
        $q = $this->base($c)->where(fn($q)=>$q->where('o.unidade_id',$unidade)->orWhere('a.unidade_id',$unidade));
        $dados = $q->select('r.id','r.tipo','r.estado','r.origem_codigo','r.alvo_namespace','r.alvo_codigo','r.alvo_codigo_bruto','r.uf_diferente',
            'o.unidade_id as origem_unidade_id','o.nome as origem_nome','o.uf as origem_uf','a.unidade_id as alvo_unidade_id','a.nome as alvo_nome','a.uf as alvo_uf')
            ->orderBy('r.ordem')->paginate($perPage,['*'],'page',$page);
        return ['situacao'=>'atualizada','conjunto_id'=>$c->id,'basica_id'=>$c->basica_id,'superior_id'=>$c->superior_id,
            'natureza'=>'Relação declarada na fonte; não comprova campus, identidade ou autoridade.','dados'=>$dados->toArray()];
    }

    public function lacunas(int $page = 1, int $perPage = 20): array
    {
        $this->limites($page,$perPage); $c = $this->vigente();
        if (!$c) return ['situacao'=>'edicao_sem_relacoes','dados'=>null];
        $p = $this->base($c)->join('catalogo_relacao_lacunas as l','l.relacao_id','=','r.id')
            ->select('r.id as relacao_id','r.tipo','r.origem_codigo','r.alvo_namespace','r.alvo_codigo','r.alvo_codigo_bruto','l.motivo','o.nome as origem_nome')
            ->orderBy('r.ordem')->paginate($perPage,['*'],'page',$page);
        return ['situacao'=>'atualizada','conjunto_id'=>$c->id,'origem'=>'lacunas_publicas_da_fonte','dados'=>$p->toArray()];
    }

    public function cobertura(): array
    {
        $edicoes = DB::table('catalogo_fontes as f')->join('catalogo_publicacoes as p','p.id','=','f.publicacao_id')
            ->where('p.estado','publicada')->get(['f.nome','p.id','p.ano','p.total'])->keyBy('nome');
        $c = $this->vigente();
        // Se a vigência mudou entre leituras, não combinar seus totais com outro conjunto.
        if ($c && (($edicoes['basica']->id??null) !== $c->basica_id || ($edicoes['superior']->id??null) !== $c->superior_id)) $c = null;
        return ['escolas_referencia'=>(int)($edicoes['basica']->total??0),'sedes_administrativas_ies'=>(int)($edicoes['superior']->total??0),
            'unidades_adicionais_comprovadas'=>0,'situacao_relacoes'=>$c ? 'atualizada' : 'nao_preparadas_para_edicao',
            'relacoes_declaradas'=>$c ? (int)$c->total : null,'pendencias_da_fonte'=>$c ? (int)$c->pendencias : null,
            'limite'=>'A tabela IES descreve sedes administrativas; não cobre todos os campi ou polos. Relações não criam unidades.',
            'edicoes'=>$edicoes->values()->all()];
    }

    private function limites(int $page, int $perPage): void
    {
        if ($page<1 || $page>100000 || $perPage<1 || $perPage>50) throw new InvalidArgumentException('Limites de consulta inválidos.');
    }
}
