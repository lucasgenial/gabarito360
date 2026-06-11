<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusEnum;
use App\Http\Resources\PerfilResource;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PerfilController extends BaseApiController
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $profiles = Perfil::query()
            ->where('status', StatusEnum::ACTIVE->value)
            ->orderBy('nome')
            ->get();

        return $this->successResponse(PerfilResource::collection($profiles)->resolve());
    }
}
