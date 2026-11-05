<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\ContextoWeb;

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
        $d = $this->ler('areas-conhecimento/'.$id);
        return view('disciplinas.areas.show', $d + ['retorno' => $this->retorno($r), 'disciplinas' => $d['dados']['gerir'] ? $this->opcoesArea($r,$id) : []]);
    }

    public function vincular(Request $r, int $id) { return $this->alterarVinculo($r,$id); }
    public function desvincular(Request $r, int $id) { return $this->alterarVinculo($r,$id,true); }

    private function alterarVinculo(Request $r, int $id, bool $encerrar = false)
    {
        $this->autorizar(true); $retorno = $this->retorno($r);
        $d = $this->validarFormulario($r,['versao'=>'required|integer|min:1']);
        if ($encerrar) $d += $this->validarFormulario($r,['disciplina_id'=>'required|integer|min:1','disciplina_versao'=>'required|integer|min:1','vinculo_id'=>'required|integer|min:1','confirmacao'=>'nullable|string|max:4096']);
        else {
            $selecionada = $this->validarFormulario($r,['disciplina_selecao'=>'required|regex:/^[1-9][0-9]*:[1-9][0-9]*$/']);
            [$d['disciplina_id'],$d['disciplina_versao']] = array_map('intval',explode(':',$selecionada['disciplina_selecao']));
        }
        $resp = $this->escrever('post',"areas-conhecimento/$id/".($encerrar?'desvincular':'vinculos'),$d);
        $voltar = redirect()->route('areas.show',['id'=>$id]+$retorno);
        if ($resp && $resp->status()===409 && $resp->json('codigo')==='confirmacao_desvinculacao') {
            return $voltar->with('confirmacao_area',['area_id'=>$id,'contexto'=>ContextoWeb::id(),'dados'=>$d,'confirmacao'=>$resp->json('data.confirmacao'),'provas'=>$resp->json('data.provas')]);
        }
        if (!$resp || $resp->failed()) return $voltar->withInput($r->only('disciplina_selecao'))->with('erro',$resp?->json('message') ?: 'Não foi possível concluir. Confira a tabela antes de tentar novamente.');
        return $voltar->with('sucesso',$encerrar?'Vínculo encerrado. O histórico foi preservado.':'Disciplina vinculada à área.')->with('foco_area',$encerrar?'area-historico':'area-disciplina');
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
        $d = $this->validarFormulario($r, ['nome' => 'required|string|max:100', 'sigla' => 'nullable|string|max:20', 'ativo' => 'required|in:0,1', 'versao' => $id ? 'required|integer|min:1' : 'prohibited']);
        $d += $this->validarFormulario($r, ['disciplina_ids' => 'sometimes|array|max:200', 'disciplina_ids.*' => 'required|integer|min:1|distinct',
            'disciplina_versoes' => 'required_with:disciplina_ids|array|max:5000', 'disciplina_versoes.*' => 'required|integer|min:1']);
        $resp = $this->escrever($id ? 'put' : 'post', 'areas-conhecimento'.($id ? '/'.$id : ''), $d);
        if (!$resp || $resp->failed()) return $this->erro($r, $resp, $retorno);
        return redirect()->route('areas.show', ['id' => $resp->json('data.id')] + $retorno)->with('sucesso', $id ? 'Área atualizada.' : 'Área adicionada à escola.');
    }
}
