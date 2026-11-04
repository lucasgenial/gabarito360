<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\GestaoDisciplinas;
use App\Support\{Permissao, ReferenciasDisciplinas};
use Illuminate\Http\{JsonResponse, Request};

class DisciplinaController extends Controller
{
    public function __construct(private GestaoDisciplinas $gestao) {}

    public function opcoes(Request $r): JsonResponse
    {
        $this->gestao->contexto($r->user()); $gerir = $this->gestao->gerir($r->user());
        $d = $r->validate(['disciplina_id' => 'nullable|integer|min:1']);
        return ApiResponse::success(['referencias' => ReferenciasDisciplinas::todas(), 'gerir' => $gerir,
            'areas' => $gerir ? $this->gestao->areasDisponiveis($r->user(), $d['disciplina_id'] ?? null) : []]);
    }

    public function professores(Request $r): JsonResponse
    {
        $d = $r->validate(['busca' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1|max:10000']);
        $q = $this->gestao->professores($r->user());
        if ($busca = trim($d['busca'] ?? '')) $q->where('u.nome', 'like', '%'.$busca.'%');
        $p = $q->paginate(20);
        return ApiResponse::success(['professores' => $p->items()], ['pagina' => $p->currentPage(), 'paginas' => $p->lastPage(), 'total' => $p->total()]);
    }

    public function index(Request $r): JsonResponse
    {
        $dados = $r->validate(['busca' => 'nullable|string|max:100', 'ativo' => 'nullable|boolean', 'page' => 'nullable|integer|min:1|max:10000']);
        $q = $this->gestao->disciplinas($r->user());
        if ($busca = trim($dados['busca'] ?? '')) $q->where(fn ($q) => $q->where('d.nome', 'like', '%'.$busca.'%')->orWhere('d.sigla', 'like', '%'.$busca.'%'));
        if ($r->filled('ativo')) $q->where('d.ativo', (bool) $dados['ativo']);
        $p = $q->orderBy('d.nome')->orderBy('d.id')->paginate(20);
        $v = $this->gestao->contexto($r->user());
        $areas = \Illuminate\Support\Facades\DB::table('disciplina_areas as da')->join('areas_conhecimento as a', function ($j) {
            $j->on('a.id', '=', 'da.area_id')->on('a.espaco_id', '=', 'da.espaco_id');
        })->where('da.espaco_id', $v->espaco_id)->whereIn('da.disciplina_id', $p->getCollection()->pluck('id'))
            ->orderBy('a.nome')->orderBy('a.id')->get(['da.disciplina_id', 'a.id', 'a.nome', 'a.sigla', 'a.ativo'])->groupBy('disciplina_id');
        $p->getCollection()->transform(function ($d) use ($areas) {
            $d->areas = ($areas->get($d->id) ?? collect())->map(function ($a) { $c = clone $a; unset($c->disciplina_id); return $c; })->all(); return $d;
        });
        return ApiResponse::success(['disciplinas' => $p->items(), 'gerir' => $this->gestao->gerir($r->user())],
            ['pagina' => $p->currentPage(), 'paginas' => $p->lastPage(), 'total' => $p->total(), 'por_pagina' => $p->perPage()]);
    }

    public function show(Request $r, int $id): JsonResponse
    {
        return ApiResponse::success(['disciplina' => $this->gestao->disciplina($r->user(), $id), 'professores' => $this->gestao->indicacoes($r->user(), $id), 'gerir' => $this->gestao->gerir($r->user())]);
    }

    public function store(Request $r): JsonResponse
    {
        $this->gestao->contexto($r->user(), true);
        return ApiResponse::success($this->gestao->salvar($r->user(), $this->validarDisciplina($r)), null, 201);
    }

    public function update(Request $r, int $id): JsonResponse
    {
        $this->gestao->contexto($r->user(), true);
        return ApiResponse::success($this->gestao->salvar($r->user(), $this->validarDisciplina($r, true), $id));
    }

    public function indicar(Request $r, int $id): JsonResponse
    {
        $this->gestao->contexto($r->user(), true);
        $d = $r->validate(['vinculo_id' => 'required|integer|min:1', 'ativo' => 'required|boolean', 'versao' => 'nullable|integer|min:1', 'espaco_id' => 'prohibited', 'escola_id' => 'prohibited', 'usuario_id' => 'prohibited']);
        return ApiResponse::success($this->gestao->indicar($r->user(), $id, $d));
    }

    public function turma(Request $r, int $turma): JsonResponse
    {
        $t = $this->gestao->turma($r->user(), $turma);
        return ApiResponse::success(['turma' => ['id' => $t->id, 'nome' => $t->nome, 'ano_letivo' => $t->ano_letivo, 'ativo' => $t->ativo],
            'componentes' => $this->gestao->componentes($r->user(), $turma), 'gerir' => $this->gestao->gerir($r->user()),
            'atribuir' => $this->gestao->gerir($r->user()) && Permissao::pode($r->user(), 'turma.vincular_professor')]);
    }

    public function adicionar(Request $r, int $turma): JsonResponse
    {
        $this->gestao->contexto($r->user(), true);
        $d = $r->validate(['disciplina_id' => 'required|integer|min:1', 'espaco_id' => 'prohibited', 'escola_id' => 'prohibited']);
        return ApiResponse::success($this->gestao->adicionarComponente($r->user(), $turma, $d['disciplina_id']), null, 201);
    }

    public function componente(Request $r, int $turma, int $componente): JsonResponse
    {
        $c = $this->gestao->componente($r->user(), $turma, $componente); $gerir = $this->gestao->gerir($r->user());
        return ApiResponse::success(['componente' => $c, 'gerir' => $gerir, 'atribuir' => $gerir && Permissao::pode($r->user(), 'turma.vincular_professor'),
            'elegiveis' => $gerir ? $this->gestao->elegiveis($r->user(), $turma, $c->disciplina_id) : [],
            'indicacoes' => $gerir ? $this->gestao->indicacoes($r->user(), $c->disciplina_id) : []]);
    }

    public function atribuir(Request $r, int $turma, int $componente): JsonResponse
    {
        $this->gestao->contexto($r->user(), true);
        $d = $r->validate(['vinculo_id' => 'required|integer|min:1', 'inicio_em' => 'required|date_format:Y-m-d', 'fim_em' => 'nullable|date_format:Y-m-d|after:inicio_em',
            'papel' => 'required|in:titular,codocencia,substituicao', 'chave' => 'required|uuid', 'versao' => 'required|integer|min:1', 'espaco_id' => 'prohibited', 'escola_id' => 'prohibited']);
        return ApiResponse::success($this->gestao->atribuir($r->user(), $turma, $componente, $d), null, 201);
    }

    public function situacao(Request $r, int $turma, int $componente): JsonResponse
    {
        $this->gestao->contexto($r->user(), true);
        $d = $r->validate(['ativo' => 'required|boolean', 'versao' => 'required|integer|min:1', 'espaco_id' => 'prohibited', 'escola_id' => 'prohibited']);
        return ApiResponse::success($this->gestao->situacaoComponente($r->user(), $turma, $componente, $d));
    }

    public function encerrar(Request $r, int $turma, int $componente, int $atribuicao): JsonResponse
    {
        $this->gestao->contexto($r->user(), true);
        $d = $r->validate(['versao' => 'required|integer|min:1', 'acao' => 'required|in:encerrar,cancelar', 'espaco_id' => 'prohibited', 'escola_id' => 'prohibited']);
        return ApiResponse::success($this->gestao->encerrar($r->user(), $turma, $componente, $atribuicao, $d));
    }

    private function validarDisciplina(Request $r, bool $editar = false): array
    {
        return $r->validate(['nome' => 'required|string|max:100', 'sigla' => 'nullable|string|max:20', 'ativo' => 'required|boolean',
            'area_ids' => 'sometimes|array|max:200', 'area_ids.*' => 'required|integer|min:1|distinct',
            'referencia' => $editar ? 'prohibited' : 'nullable|string|max:50', 'versao' => ($editar ? 'required' : 'prohibited').'|integer|min:1',
            'espaco_id' => 'prohibited', 'escola_id' => 'prohibited']);
    }
}
