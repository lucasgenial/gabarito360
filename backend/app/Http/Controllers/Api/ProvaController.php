<?php

namespace App\Http\Controllers\Api;

use App\Actions\Provas\CreateProvaAction;
use App\Actions\Provas\CreateQuestaoAction;
use App\Actions\Provas\UpdateProvaAction;
use App\Actions\Provas\UpdateQuestaoAction;
use App\Http\Requests\Provas\ListProvasRequest;
use App\Http\Requests\Provas\ListQuestoesRequest;
use App\Http\Requests\Provas\StoreProvaRequest;
use App\Http\Requests\Provas\StoreQuestaoRequest;
use App\Http\Requests\Provas\UpdateProvaRequest;
use App\Http\Requests\Provas\UpdateQuestaoRequest;
use App\Http\Requests\Provas\ViewQuestaoRequest;
use App\Http\Resources\ProvaResource;
use App\Http\Resources\QuestaoResource;
use App\Models\Prova;
use App\Models\User;
use App\Services\Authorization\ProvaScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProvaController extends BaseApiController
{
    public function index(ListProvasRequest $request, ProvaScope $scope): JsonResponse
    {
        Gate::authorize('viewAny', Prova::class);

        $filters = $request->validated();
        $query = $scope->apply(Prova::query(), $this->actor($request->user()))
            ->orderByDesc('created_at')
            ->orderBy('id');

        foreach (['nucleo_id', 'escola_id', 'modelo_cartao_id', 'status'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (isset($filters['search'])) {
            $query->where(function ($search) use ($filters): void {
                $search
                    ->where('titulo', 'ilike', '%'.$filters['search'].'%')
                    ->orWhere('codigo', 'ilike', '%'.$filters['search'].'%');
            });
        }

        $paginator = $query->paginate($filters['per_page'] ?? 20);

        return $this->paginatedResponse($request, $paginator, ProvaResource::class);
    }

    public function store(StoreProvaRequest $request, CreateProvaAction $action): JsonResponse
    {
        $exam = $action->execute($request->validated(), $this->actor($request->user()));

        return $this->successResponse(ProvaResource::make($exam)->resolve($request), 201);
    }

    public function show(Request $request, string $prova, ProvaScope $scope): JsonResponse
    {
        $exam = $scope->apply(Prova::query(), $this->actor($request->user()))->findOrFail($prova);

        return $this->successResponse(ProvaResource::make($exam)->resolve($request));
    }

    public function update(UpdateProvaRequest $request, UpdateProvaAction $action): JsonResponse
    {
        $exam = $action->execute(
            $request->prova(),
            $request->validated(),
            $this->actor($request->user()),
        );

        return $this->successResponse(ProvaResource::make($exam)->resolve($request));
    }

    public function questions(ListQuestoesRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = $request->prova()->questoes()->orderBy('numero')->orderBy('id');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $paginator = $query->paginate($filters['per_page'] ?? 100);

        return $this->paginatedResponse($request, $paginator, QuestaoResource::class);
    }

    public function storeQuestion(StoreQuestaoRequest $request, CreateQuestaoAction $action): JsonResponse
    {
        $question = $action->execute(
            $request->prova(),
            $request->validated(),
            $this->actor($request->user()),
        );

        return $this->successResponse(QuestaoResource::make($question)->resolve($request), 201);
    }

    public function showQuestion(ViewQuestaoRequest $request): JsonResponse
    {
        return $this->successResponse(QuestaoResource::make($request->questao())->resolve($request));
    }

    public function updateQuestion(UpdateQuestaoRequest $request, UpdateQuestaoAction $action): JsonResponse
    {
        $question = $action->execute(
            $request->prova(),
            $request->questao(),
            $request->validated(),
            $this->actor($request->user()),
        );

        return $this->successResponse(QuestaoResource::make($question)->resolve($request));
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }

    /** @param class-string<ProvaResource|QuestaoResource> $resource */
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
