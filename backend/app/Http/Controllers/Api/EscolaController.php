<?php

namespace App\Http\Controllers\Api;

use App\Actions\Escolas\CreateEscolaAction;
use App\Actions\Escolas\InactivateEscolaAction;
use App\Actions\Escolas\UpdateEscolaAction;
use App\Http\Requests\Escolas\ListEscolasRequest;
use App\Http\Requests\Escolas\StoreEscolaRequest;
use App\Http\Requests\Escolas\UpdateEscolaRequest;
use App\Http\Resources\EscolaResource;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\User;
use App\Services\Authorization\SchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EscolaController extends BaseApiController
{
    public function index(ListEscolasRequest $request, SchoolScope $schoolScope): JsonResponse
    {
        Gate::authorize('viewAny', Escola::class);

        $filters = $request->validated();
        $query = $schoolScope->apply(Escola::query(), $this->actor($request->user()))
            ->orderBy('nome')
            ->orderBy('id');

        if (isset($filters['nucleo_id'])) {
            $query->where('nucleo_id', $filters['nucleo_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($query) use ($filters): void {
                $query
                    ->where('nome', 'like', '%'.$filters['search'].'%')
                    ->orWhere('codigo', 'like', '%'.$filters['search'].'%');
            });
        }

        $paginator = $query->paginate($filters['per_page'] ?? 20);

        return $this->successResponse([
            'items' => EscolaResource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreEscolaRequest $request, CreateEscolaAction $action): JsonResponse
    {
        $attributes = $request->validated();
        $nucleo = Nucleo::query()->findOrFail($attributes['nucleo_id']);

        Gate::authorize('create', [Escola::class, $nucleo]);

        $escola = $action->execute($attributes, $this->actor($request->user()));

        return $this->successResponse(EscolaResource::make($escola)->resolve($request), 201);
    }

    public function show(Request $request, Escola $escola): JsonResponse
    {
        Gate::authorize('view', $escola);

        return $this->successResponse(EscolaResource::make($escola)->resolve($request));
    }

    public function update(
        UpdateEscolaRequest $request,
        Escola $escola,
        UpdateEscolaAction $action,
    ): JsonResponse {
        Gate::authorize('update', $escola);

        $escola = $action->execute($escola, $request->validated(), $this->actor($request->user()));

        return $this->successResponse(EscolaResource::make($escola)->resolve($request));
    }

    public function destroy(Request $request, Escola $escola, InactivateEscolaAction $action): JsonResponse
    {
        Gate::authorize('delete', $escola);

        $escola = $action->execute($escola, $this->actor($request->user()));

        return $this->successResponse(EscolaResource::make($escola)->resolve($request));
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
