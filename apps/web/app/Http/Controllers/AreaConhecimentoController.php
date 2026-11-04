<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Reutiliza os mesmos controles de contexto, retorno e recuperação de conflito do DC1. */
class AreaConhecimentoController extends DisciplinaController
{
    public function index(Request $r)
    {
        $this->autorizar();
        $f = $r->validate(['busca' => 'nullable|string|max:100', 'ativo' => 'nullable|in:0,1', 'page' => 'nullable|integer|min:1|max:10000']);
        return view('disciplinas.areas.index', $this->ler('areas-conhecimento', array_filter($f, fn ($v) => $v !== null && $v !== '')) + ['f' => $f, 'retorno' => $this->retorno($r)]);
    }

    public function create(Request $r)
    {
        if ($resposta = $this->rever($r)) return $resposta;
        $this->autorizar(true);
        return view('disciplinas.areas.form', ['area' => null, 'retorno' => $this->retorno($r), 'disciplinas' => $this->opcoesArea($r)]);
    }

    public function edit(Request $r, int $id)
    {
        if ($resposta = $this->rever($r)) return $resposta;
        $this->autorizar(true); $d = $this->ler('areas-conhecimento/'.$id)['dados']; abort_unless($d['gerir'], 403);
        return view('disciplinas.areas.form', ['area' => $d['area'], 'retorno' => $this->retorno($r), 'disciplinas' => $this->opcoesArea($r, $id)]);
    }

    public function show(Request $r, int $id)
    {
        $this->autorizar();
        return view('disciplinas.areas.show', $this->ler('areas-conhecimento/'.$id) + ['retorno' => $this->retorno($r)]);
    }

    public function store(Request $r) { return $this->salvarArea($r); }
    public function update(Request $r, int $id) { return $this->salvarArea($r, $id); }

    private function opcoesArea(Request $r, ?int $id = null): array
    {
        $ids = collect((array) $r->old('disciplina_ids', []))->filter(fn ($v) => is_scalar($v) && ctype_digit((string) $v) && (int) $v > 0)->map(fn ($v) => (int) $v)->unique()->take(200)->values()->all();
        return $this->ler('areas-conhecimento/opcoes', array_filter(['area_id' => $id, 'rascunho_ids' => $ids], fn ($v) => $v !== null))['dados']['disciplinas'];
    }

    private function salvarArea(Request $r, ?int $id = null)
    {
        $this->autorizar(true); $retorno = $this->retorno($r);
        if ($r->boolean('disciplinas_selecao')) $r->merge(['disciplina_ids' => $r->input('disciplina_ids', [])]);
        $d = $r->validate(['nome' => 'required|string|max:100', 'sigla' => 'nullable|string|max:20', 'ativo' => 'required|in:0,1', 'versao' => $id ? 'required|integer|min:1' : 'prohibited']);
        $d += $r->validate(['disciplina_ids' => 'sometimes|array|max:200', 'disciplina_ids.*' => 'required|integer|min:1|distinct',
            'disciplina_versoes' => 'required_with:disciplina_ids|array|max:5000', 'disciplina_versoes.*' => 'required|integer|min:1']);
        $resp = $this->escrever($id ? 'put' : 'post', 'areas-conhecimento'.($id ? '/'.$id : ''), $d);
        if (!$resp || $resp->failed()) return $this->erro($r, $resp);
        return redirect()->route('areas.show', ['id' => $resp->json('data.id')] + $retorno)->with('sucesso', $id ? 'Área atualizada.' : 'Área adicionada à escola.');
    }
}
