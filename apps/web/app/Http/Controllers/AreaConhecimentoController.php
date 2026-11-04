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
        $this->autorizar(true);
        return view('disciplinas.areas.form', ['area' => null, 'retorno' => $this->retorno($r)]);
    }

    public function edit(Request $r, int $id)
    {
        if ($resposta = $this->rever($r)) return $resposta;
        $this->autorizar(true); $d = $this->ler('areas-conhecimento/'.$id)['dados']; abort_unless($d['gerir'], 403);
        return view('disciplinas.areas.form', ['area' => $d['area'], 'retorno' => $this->retorno($r)]);
    }

    public function show(Request $r, int $id)
    {
        $this->autorizar();
        return view('disciplinas.areas.show', $this->ler('areas-conhecimento/'.$id) + ['retorno' => $this->retorno($r)]);
    }

    public function store(Request $r) { return $this->salvarArea($r); }
    public function update(Request $r, int $id) { return $this->salvarArea($r, $id); }

    private function salvarArea(Request $r, ?int $id = null)
    {
        $this->autorizar(true); $retorno = $this->retorno($r);
        $d = $r->validate(['nome' => 'required|string|max:100', 'sigla' => 'nullable|string|max:20', 'ativo' => 'required|in:0,1', 'versao' => $id ? 'required|integer|min:1' : 'prohibited']);
        $resp = $this->escrever($id ? 'put' : 'post', 'areas-conhecimento'.($id ? '/'.$id : ''), $d);
        if (!$resp || $resp->failed()) return $this->erro($r, $resp);
        return redirect()->route('areas.show', ['id' => $resp->json('data.id')] + $retorno)->with('sucesso', $id ? 'Área atualizada.' : 'Área adicionada à escola.');
    }
}
