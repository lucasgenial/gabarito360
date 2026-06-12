<?php

namespace App\Http\Controllers\Api;

use App\Actions\Alunos\CreateAlunoAction;
use App\Actions\Alunos\InactivateAlunoAction;
use App\Actions\Alunos\UpdateAlunoAction;
use App\Http\Requests\Alunos\ListAlunosRequest;
use App\Http\Requests\Alunos\ManageAlunoRequest;
use App\Http\Requests\Alunos\StoreAlunoRequest;
use App\Http\Requests\Alunos\UpdateAlunoRequest;
use App\Http\Resources\AlunoResource;
use App\Http\Resources\AlunoResumoResource;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\User;
use App\Services\Authorization\AlunoScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AlunoController extends BaseApiController
{
    public function index(ListAlunosRequest $request, AlunoScope $scope): JsonResponse
    {
        Gate::authorize('viewAny', Aluno::class);

        $actor = $this->actor($request->user());
        $filters = $request->validated();
        $query = $scope->apply(Aluno::query(), $actor)->orderBy('nome')->orderBy('id');

        foreach (['escola_id', 'status'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (isset($filters['turma_id'])) {
            $query->whereHas(
                'matriculasTurmas',
                fn (Builder $enrollments) => $enrollments->where('turma_id', $filters['turma_id']),
            );
        }

        if (isset($filters['search'])) {
            $query->where(function (Builder $search) use ($filters): void {
                $search
                    ->where('nome', 'like', '%'.$filters['search'].'%')
                    ->orWhere('matricula', 'like', '%'.$filters['search'].'%');
            });
        }

        return $this->paginatedResponse(
            $request,
            $query->paginate($filters['per_page'] ?? 20),
            $scope,
            $actor,
        );
    }

    public function store(StoreAlunoRequest $request, CreateAlunoAction $action): JsonResponse
    {
        $attributes = $request->validated();
        $school = Escola::query()->findOrFail($attributes['escola_id']);
        Gate::authorize('create', [Aluno::class, $school]);

        $student = $action->execute($attributes, $this->actor($request->user()));

        return $this->successResponse(AlunoResource::make($student)->resolve($request), 201);
    }

    public function show(Request $request, string $aluno, AlunoScope $scope): JsonResponse
    {
        $actor = $this->actor($request->user());
        $student = $scope->apply(Aluno::query(), $actor)->findOrFail($aluno);

        return $this->successResponse($this->resolveResource($request, $scope, $actor, $student));
    }

    public function update(UpdateAlunoRequest $request, UpdateAlunoAction $action): JsonResponse
    {
        $student = $action->execute(
            $request->student(),
            $request->validated(),
            $this->actor($request->user()),
        );

        return $this->successResponse(AlunoResource::make($student)->resolve($request));
    }

    public function destroy(ManageAlunoRequest $request, InactivateAlunoAction $action): JsonResponse
    {
        $student = $action->execute($request->student(), $this->actor($request->user()));

        return $this->successResponse(AlunoResource::make($student)->resolve($request));
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }

    private function paginatedResponse(
        Request $request,
        LengthAwarePaginator $paginator,
        AlunoScope $scope,
        User $actor,
    ): JsonResponse {
        return $this->successResponse([
            'items' => $paginator->getCollection()
                ->map(fn (Aluno $student): array => $this->resolveResource($request, $scope, $actor, $student))
                ->values()
                ->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function resolveResource(Request $request, AlunoScope $scope, User $actor, Aluno $student): array
    {
        $resource = $scope->canManage($actor, $student) ? AlunoResource::class : AlunoResumoResource::class;

        return $resource::make($student)->resolve($request);
    }
}
