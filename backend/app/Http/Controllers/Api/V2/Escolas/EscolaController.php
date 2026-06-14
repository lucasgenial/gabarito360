<?php

namespace App\Http\Controllers\Api\V2\Escolas;

use App\Actions\Escolas\CreateEscolaAction;
use App\Actions\Escolas\ReactivateEscolaAction;
use App\Actions\Escolas\UpdateEscolaAction;
use App\Enums\StatusEnum;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Escolas\ListEscolasRequest;
use App\Http\Requests\Api\V2\Escolas\StoreEscolaRequest;
use App\Http\Requests\Api\V2\Escolas\UpdateEscolaRequest;
use App\Http\Resources\Api\V2\EscolaResource;
use App\Models\Escola;
use App\Services\Authorization\SchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EscolaController extends BaseApiController
{
    public function index(ListEscolasRequest $request, SchoolScope $scope): JsonResponse
    {
        $query = $scope->apply(Escola::query(), $this->actor($request));

        if (is_string($q = $request->input('q')) && $q !== '') {
            $query->where('nome', 'like', '%'.$q.'%');
        }

        if (is_string($status = $request->input('status'))) {
            $query->where('status', $status === 'ativa' ? StatusEnum::ACTIVE->value : StatusEnum::INACTIVE->value);
        }

        return $this->paginatedResponse(
            $query->orderBy('nome')->paginate(15),
            EscolaResource::class,
        );
    }

    public function store(StoreEscolaRequest $request, CreateEscolaAction $action): JsonResponse
    {
        $escola = $action->execute($request->mappedAttributes(), $this->actor($request));

        return $this->successResponse(EscolaResource::make($escola), 201);
    }

    public function show(Request $request, Escola $escola): JsonResponse
    {
        Gate::authorize('view', $escola);

        return $this->successResponse(EscolaResource::make($escola));
    }

    public function update(UpdateEscolaRequest $request, Escola $escola, UpdateEscolaAction $action): JsonResponse
    {
        $escola = $action->execute($escola, $request->mappedAttributes(), $this->actor($request));

        return $this->successResponse(EscolaResource::make($escola));
    }

    public function reactivate(Request $request, Escola $escola, ReactivateEscolaAction $action): JsonResponse
    {
        Gate::authorize('update', $escola);

        $escola = $action->execute($escola, $this->actor($request));

        return $this->successResponse(EscolaResource::make($escola));
    }

    public function indicadores(Request $request, Escola $escola): JsonResponse
    {
        Gate::authorize('view', $escola);

        $membros = $escola->usuarioPerfis()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->distinct()
            ->count('usuario_id');

        return $this->successResponse([
            'kpis' => [
                'turmas' => $escola->turmas()->count(),
                'alunos' => $escola->alunos()->count(),
                'provas' => $escola->provas()->count(),
                'aplicacoes' => $escola->aplicacoes()->count(),
                'membros' => $membros,
            ],
            'desempenho' => [],
            'distribuicao' => [],
            'evolucao' => [],
        ]);
    }
}
