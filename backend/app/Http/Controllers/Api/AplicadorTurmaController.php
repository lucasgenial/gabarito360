<?php

namespace App\Http\Controllers\Api;

use App\Actions\Turmas\AssignAplicadorTurmaAction;
use App\Actions\Turmas\CloseAplicadorTurmaAction;
use App\Http\Requests\Turmas\CloseAplicadorTurmaRequest;
use App\Http\Requests\Turmas\ListAplicadoresTurmaRequest;
use App\Http\Requests\Turmas\StoreAplicadorTurmaRequest;
use App\Http\Resources\AplicadorTurmaResource;
use App\Models\AplicadorTurma;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AplicadorTurmaController extends BaseApiController
{
    public function index(ListAplicadoresTurmaRequest $request, Turma $turma): JsonResponse
    {
        Gate::authorize('closeStaff', $turma);

        $filters = $request->validated();
        $query = $turma->aplicadores()->orderByDesc('inicio_em')->orderBy('id');

        if (isset($filters['papel'])) {
            $query->where('papel', $filters['papel']);
        }

        if ($request->boolean('ativos', true)) {
            $query->whereNull('fim_em');
        }

        $paginator = $query->paginate($filters['per_page'] ?? 20);

        return $this->successResponse([
            'items' => AplicadorTurmaResource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(
        StoreAplicadorTurmaRequest $request,
        Turma $turma,
        AssignAplicadorTurmaAction $action,
    ): JsonResponse {
        Gate::authorize('assignStaff', $turma);
        $link = $action->execute($turma, $request->validated(), $this->actor($request->user()));

        return $this->successResponse(AplicadorTurmaResource::make($link)->resolve($request), 201);
    }

    public function destroy(
        CloseAplicadorTurmaRequest $request,
        Turma $turma,
        AplicadorTurma $vinculo,
        CloseAplicadorTurmaAction $action,
    ): JsonResponse {
        abort_unless($vinculo->turma_id === $turma->id, 404);
        Gate::authorize('closeStaff', $turma);
        $link = $action->execute($turma, $vinculo, $request->validated(), $this->actor($request->user()));

        return $this->successResponse(AplicadorTurmaResource::make($link)->resolve($request));
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
