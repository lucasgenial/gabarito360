<?php

namespace App\Http\Controllers\Api;

use App\Actions\Nucleos\CreateNucleoAction;
use App\Actions\Nucleos\InactivateNucleoAction;
use App\Actions\Nucleos\UpdateNucleoAction;
use App\Http\Requests\Nucleos\ListNucleosRequest;
use App\Http\Requests\Nucleos\StoreNucleoRequest;
use App\Http\Requests\Nucleos\UpdateNucleoRequest;
use App\Http\Resources\NucleoResource;
use App\Models\Nucleo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NucleoController extends BaseApiController
{
    public function index(ListNucleosRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Nucleo::class);

        $filters = $request->validated();
        $query = Nucleo::query()->orderBy('nome')->orderBy('id');

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
            'items' => NucleoResource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreNucleoRequest $request, CreateNucleoAction $action): JsonResponse
    {
        Gate::authorize('create', Nucleo::class);

        $nucleo = $action->execute($request->validated(), $this->actor($request->user()));

        return $this->successResponse(NucleoResource::make($nucleo)->resolve($request), 201);
    }

    public function show(Request $request, Nucleo $nucleo): JsonResponse
    {
        Gate::authorize('view', $nucleo);

        return $this->successResponse(NucleoResource::make($nucleo)->resolve($request));
    }

    public function update(
        UpdateNucleoRequest $request,
        Nucleo $nucleo,
        UpdateNucleoAction $action,
    ): JsonResponse {
        Gate::authorize('update', $nucleo);

        $nucleo = $action->execute($nucleo, $request->validated(), $this->actor($request->user()));

        return $this->successResponse(NucleoResource::make($nucleo)->resolve($request));
    }

    public function destroy(Request $request, Nucleo $nucleo, InactivateNucleoAction $action): JsonResponse
    {
        Gate::authorize('delete', $nucleo);

        $nucleo = $action->execute($nucleo, $this->actor($request->user()));

        return $this->successResponse(NucleoResource::make($nucleo)->resolve($request));
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
