<?php

namespace App\Http\Controllers\Api;

use App\Actions\Turmas\CloseMatriculaTurmaAction;
use App\Actions\Turmas\CreateMatriculaTurmaAction;
use App\Actions\Turmas\CreateTurmaAction;
use App\Actions\Turmas\InactivateTurmaAction;
use App\Actions\Turmas\UpdateTurmaAction;
use App\Http\Requests\Turmas\CloseMatriculaTurmaRequest;
use App\Http\Requests\Turmas\ListMatriculasTurmaRequest;
use App\Http\Requests\Turmas\ListTurmasRequest;
use App\Http\Requests\Turmas\StoreMatriculaTurmaRequest;
use App\Http\Requests\Turmas\StoreTurmaRequest;
use App\Http\Requests\Turmas\UpdateTurmaRequest;
use App\Http\Resources\MatriculaTurmaResource;
use App\Http\Resources\TurmaResource;
use App\Models\Escola;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\TurmaScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TurmaController extends BaseApiController
{
    public function index(ListTurmasRequest $request, TurmaScope $scope): JsonResponse
    {
        Gate::authorize('viewAny', Turma::class);

        $filters = $request->validated();
        $query = $scope->apply(Turma::query(), $this->actor($request->user()))
            ->orderByDesc('ano_letivo')
            ->orderBy('nome')
            ->orderBy('id');

        foreach (['escola_id', 'ano_letivo', 'status'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (isset($filters['search'])) {
            $query->where(function ($query) use ($filters): void {
                $query
                    ->where('nome', 'ilike', '%'.$filters['search'].'%')
                    ->orWhere('codigo', 'ilike', '%'.$filters['search'].'%');
            });
        }

        $paginator = $query->paginate($filters['per_page'] ?? 20);

        return $this->paginatedResponse($request, $paginator, TurmaResource::class);
    }

    public function store(StoreTurmaRequest $request, CreateTurmaAction $action): JsonResponse
    {
        $attributes = $request->validated();
        $school = Escola::query()->findOrFail($attributes['escola_id']);
        Gate::authorize('create', [Turma::class, $school]);

        $turma = $action->execute($attributes, $this->actor($request->user()));

        return $this->successResponse(TurmaResource::make($turma)->resolve($request), 201);
    }

    public function show(Request $request, Turma $turma): JsonResponse
    {
        Gate::authorize('view', $turma);

        return $this->successResponse(TurmaResource::make($turma)->resolve($request));
    }

    public function update(
        UpdateTurmaRequest $request,
        Turma $turma,
        UpdateTurmaAction $action,
    ): JsonResponse {
        Gate::authorize('update', $turma);
        $turma = $action->execute($turma, $request->validated(), $this->actor($request->user()));

        return $this->successResponse(TurmaResource::make($turma)->resolve($request));
    }

    public function destroy(Request $request, Turma $turma, InactivateTurmaAction $action): JsonResponse
    {
        Gate::authorize('delete', $turma);
        $turma = $action->execute($turma, $this->actor($request->user()));

        return $this->successResponse(TurmaResource::make($turma)->resolve($request));
    }

    public function matriculas(ListMatriculasTurmaRequest $request, Turma $turma): JsonResponse
    {
        Gate::authorize('view', $turma);

        $filters = $request->validated();
        $query = $turma->matriculas()->orderByDesc('inicio_em')->orderBy('id');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $paginator = $query->paginate($filters['per_page'] ?? 20);

        return $this->paginatedResponse($request, $paginator, MatriculaTurmaResource::class);
    }

    public function storeMatricula(
        StoreMatriculaTurmaRequest $request,
        Turma $turma,
        CreateMatriculaTurmaAction $action,
    ): JsonResponse {
        Gate::authorize('createEnrollment', $turma);
        $matricula = $action->execute($turma, $request->validated(), $this->actor($request->user()));

        return $this->successResponse(MatriculaTurmaResource::make($matricula)->resolve($request), 201);
    }

    public function closeMatricula(
        CloseMatriculaTurmaRequest $request,
        Turma $turma,
        MatriculaTurma $matricula,
        CloseMatriculaTurmaAction $action,
    ): JsonResponse {
        abort_unless($matricula->turma_id === $turma->id, 404);
        Gate::authorize('closeEnrollment', $turma);
        $matricula = $action->execute($turma, $matricula, $request->validated(), $this->actor($request->user()));

        return $this->successResponse(MatriculaTurmaResource::make($matricula)->resolve($request));
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }

    /** @param class-string<TurmaResource|MatriculaTurmaResource> $resource */
    private function paginatedResponse(Request $request, LengthAwarePaginator $paginator, string $resource): JsonResponse
    {
        return $this->successResponse([
            'items' => $resource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
