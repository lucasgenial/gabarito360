<?php

namespace App\Http\Controllers\Api\V2\Nucleos;

use App\Actions\Nucleos\CreateNucleoAction;
use App\Actions\Nucleos\ReactivateNucleoAction;
use App\Actions\Nucleos\UpdateNucleoAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Nucleos\ListNucleosRequest;
use App\Http\Requests\Api\V2\Nucleos\StoreNucleoRequest;
use App\Http\Requests\Api\V2\Nucleos\UpdateNucleoRequest;
use App\Http\Resources\Api\V2\NucleoResource;
use App\Models\Nucleo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NucleoController extends BaseApiController
{
    public function index(ListNucleosRequest $request): JsonResponse
    {
        $query = Nucleo::query();

        if (is_string($q = $request->input('q')) && $q !== '') {
            $query->where('nome', 'like', '%'.$q.'%');
        }

        if (is_string($status = $request->input('status'))) {
            $query->where('status', $status);
        }

        return $this->paginatedResponse(
            $query->orderBy('nome')->paginate(15),
            NucleoResource::class,
        );
    }

    public function store(StoreNucleoRequest $request, CreateNucleoAction $action): JsonResponse
    {
        $nucleo = $action->execute($request->validatedAttributes(), $this->actor($request));

        return $this->successResponse(NucleoResource::make($nucleo), 201);
    }

    public function show(Request $request, Nucleo $nucleo): JsonResponse
    {
        Gate::authorize('view', $nucleo);

        return $this->successResponse(NucleoResource::make($nucleo));
    }

    public function update(UpdateNucleoRequest $request, Nucleo $nucleo, UpdateNucleoAction $action): JsonResponse
    {
        $nucleo = $action->execute($nucleo, $request->validatedAttributes(), $this->actor($request));

        return $this->successResponse(NucleoResource::make($nucleo));
    }

    public function reactivate(Request $request, Nucleo $nucleo, ReactivateNucleoAction $action): JsonResponse
    {
        Gate::authorize('update', $nucleo);

        $nucleo = $action->execute($nucleo, $this->actor($request));

        return $this->successResponse(NucleoResource::make($nucleo));
    }
}
