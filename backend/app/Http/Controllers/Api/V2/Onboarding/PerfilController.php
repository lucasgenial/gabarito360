<?php

namespace App\Http\Controllers\Api\V2\Onboarding;

use App\Enums\StatusEnum;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\PerfilPublicoResource;
use App\Models\Perfil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class PerfilController extends BaseApiController
{
    public function __invoke(): JsonResponse
    {
        $perfis = Perfil::query()
            ->where('sistema', true)
            ->where('status', StatusEnum::ACTIVE->value)
            ->with('permissoes')
            ->withCount(['usuarioVinculos as membros_count' => function (Builder $query): void {
                $query->where('inicio_at', '<=', now())->whereNull('fim_at');
            }])
            ->orderBy('nome')
            ->get();

        return $this->successResponse(PerfilPublicoResource::collection($perfis));
    }
}
