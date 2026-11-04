<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\{GestaoAreasConhecimento, GestaoDisciplinas};
use Illuminate\Http\{JsonResponse, Request};

class AreaConhecimentoController extends Controller
{
    public function __construct(private GestaoAreasConhecimento $areas, private GestaoDisciplinas $disciplinas) {}

    public function index(Request $r): JsonResponse
    {
        $d = $r->validate(['busca' => 'nullable|string|max:100', 'ativo' => 'nullable|boolean', 'page' => 'nullable|integer|min:1|max:10000']);
        $q = $this->areas->consultar($r->user());
        if ($busca = trim($d['busca'] ?? '')) $q->where(fn ($w) => $w->where('nome', 'like', '%'.$busca.'%')->orWhere('sigla', 'like', '%'.$busca.'%'));
        if ($r->filled('ativo')) $q->where('ativo', (bool) $d['ativo']);
        $p = $q->orderBy('nome')->orderBy('id')->paginate(20);
        return ApiResponse::success(['areas' => $p->items(), 'gerir' => $this->disciplinas->gerir($r->user())],
            ['pagina' => $p->currentPage(), 'paginas' => $p->lastPage(), 'total' => $p->total()]);
    }

    public function show(Request $r, int $id): JsonResponse
    {
        return ApiResponse::success(['area' => $this->areas->area($r->user(), $id), 'gerir' => $this->disciplinas->gerir($r->user())]);
    }

    public function opcoes(Request $r): JsonResponse
    {
        $d = $r->validate(['area_id' => 'nullable|integer|min:1', 'rascunho_ids' => 'sometimes|array|max:200', 'rascunho_ids.*' => 'integer|min:1|distinct']);
        return ApiResponse::success(['disciplinas' => $this->areas->opcoes($r->user(), $d['area_id'] ?? null, $d['rascunho_ids'] ?? [])]);
    }

    public function store(Request $r): JsonResponse { return $this->salvar($r); }
    public function update(Request $r, int $id): JsonResponse { return $this->salvar($r, $id); }

    private function salvar(Request $r, ?int $id = null): JsonResponse
    {
        $this->disciplinas->contexto($r->user(), true);
        $d = $r->validate(['nome' => 'required|string|max:100', 'sigla' => 'nullable|string|max:20', 'ativo' => 'required|boolean',
            'disciplina_ids' => 'sometimes|array|max:200', 'disciplina_ids.*' => 'required|integer|min:1|distinct',
            'disciplina_versoes' => 'required_with:disciplina_ids|array|max:5000', 'disciplina_versoes.*' => 'required|integer|min:1',
            'versao' => ($id ? 'required' : 'prohibited').'|integer|min:1', 'espaco_id' => 'prohibited', 'escola_id' => 'prohibited']);
        return ApiResponse::success($this->areas->salvar($r->user(), $d, $id), null, $id ? 200 : 201);
    }
}
