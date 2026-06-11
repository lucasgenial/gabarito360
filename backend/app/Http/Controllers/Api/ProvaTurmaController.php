<?php

namespace App\Http\Controllers\Api;

use App\Actions\Provas\LinkProvaTurmaAction;
use App\Actions\Provas\UnlinkProvaTurmaAction;
use App\Http\Requests\Provas\DestroyProvaTurmaRequest;
use App\Http\Requests\Provas\ListProvaTurmasRequest;
use App\Http\Requests\Provas\StoreProvaTurmaRequest;
use App\Http\Resources\ProvaTurmaResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ProvaTurmaController extends BaseApiController
{
    public function index(ListProvaTurmasRequest $request): JsonResponse
    {
        $paginator = $request->prova()
            ->provaTurmas()
            ->with('turma.escola')
            ->orderBy('data_prevista')
            ->orderBy('created_at')
            ->paginate($request->validated('per_page', 20));

        return $this->successResponse([
            'items' => ProvaTurmaResource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreProvaTurmaRequest $request, LinkProvaTurmaAction $action): JsonResponse
    {
        $link = $action->execute(
            $request->prova(),
            $request->turma(),
            $request->validated(),
            $this->actor($request->user()),
        );

        return $this->successResponse(ProvaTurmaResource::make($link)->resolve($request), 201);
    }

    public function destroy(DestroyProvaTurmaRequest $request, UnlinkProvaTurmaAction $action): JsonResponse
    {
        $action->execute(
            $request->prova(),
            $request->provaTurma(),
            $this->actor($request->user()),
        );

        return $this->successResponse();
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
