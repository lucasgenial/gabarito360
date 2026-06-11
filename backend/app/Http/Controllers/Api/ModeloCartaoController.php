<?php

namespace App\Http\Controllers\Api;

use App\Actions\ModelosCartao\ApproveModeloCartaoAction;
use App\Actions\ModelosCartao\CreateModeloCartaoAction;
use App\Actions\ModelosCartao\InactivateModeloCartaoAction;
use App\Actions\ModelosCartao\UpdateModeloCartaoAction;
use App\Http\Requests\ModelosCartao\ApproveModeloCartaoRequest;
use App\Http\Requests\ModelosCartao\InactivateModeloCartaoRequest;
use App\Http\Requests\ModelosCartao\ListModelosCartaoRequest;
use App\Http\Requests\ModelosCartao\StoreModeloCartaoRequest;
use App\Http\Requests\ModelosCartao\UpdateModeloCartaoRequest;
use App\Http\Resources\ModeloCartaoResource;
use App\Models\ModeloCartao;
use App\Models\User;
use App\Services\Authorization\ModeloCartaoScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModeloCartaoController extends BaseApiController
{
    public function index(ListModelosCartaoRequest $request, ModeloCartaoScope $scope): JsonResponse
    {
        Gate::authorize('viewAny', ModeloCartao::class);

        $actor = $this->actor($request->user());
        $filters = $request->validated();
        $query = $scope->apply(ModeloCartao::query(), $actor)
            ->orderBy('nome')
            ->orderByDesc('versao')
            ->orderBy('id');

        foreach (['nucleo_id', 'status'] as $field) {
            if (array_key_exists($field, $filters)) {
                $query->where($field, $filters[$field]);
            }
        }

        if (isset($filters['search'])) {
            $query->where('nome', 'ilike', '%'.$filters['search'].'%');
        }

        $paginator = $query->paginate($filters['per_page'] ?? 20);

        return $this->successResponse([
            'items' => ModeloCartaoResource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreModeloCartaoRequest $request, CreateModeloCartaoAction $action): JsonResponse
    {
        $model = $action->execute($request->validated(), $this->actor($request->user()));

        return $this->successResponse(ModeloCartaoResource::make($model)->resolve($request), 201);
    }

    public function show(Request $request, string $modelo, ModeloCartaoScope $scope): JsonResponse
    {
        $model = $this->scopedModel($request, $modelo, $scope);

        return $this->successResponse(ModeloCartaoResource::make($model)->resolve($request));
    }

    public function update(
        UpdateModeloCartaoRequest $request,
        UpdateModeloCartaoAction $action,
    ): JsonResponse {
        $model = $action->execute($request->cardModel(), $request->validated(), $this->actor($request->user()));

        return $this->successResponse(ModeloCartaoResource::make($model)->resolve($request));
    }

    public function approve(
        ApproveModeloCartaoRequest $request,
        ApproveModeloCartaoAction $action,
    ): JsonResponse {
        $model = $action->execute($request->cardModel(), $this->actor($request->user()));

        return $this->successResponse(ModeloCartaoResource::make($model)->resolve($request));
    }

    public function destroy(
        InactivateModeloCartaoRequest $request,
        InactivateModeloCartaoAction $action,
    ): JsonResponse {
        $model = $action->execute($request->cardModel(), $this->actor($request->user()));

        return $this->successResponse(ModeloCartaoResource::make($model)->resolve($request));
    }

    private function scopedModel(Request $request, string $id, ModeloCartaoScope $scope): ModeloCartao
    {
        return $scope->apply(ModeloCartao::query(), $this->actor($request->user()))->findOrFail($id);
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
