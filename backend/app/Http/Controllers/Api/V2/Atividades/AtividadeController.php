<?php

namespace App\Http\Controllers\Api\V2\Atividades;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\AtividadeResource;
use App\Models\AtividadeRecente;
use App\Services\Authorization\PortalScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AtividadeController extends BaseApiController
{
    public function __construct(private PortalScope $scope) {}

    /**
     * Feed de atividade recente no escopo do ator (escola/núcleo das lotações).
     * Ator com visão de rede vê tudo. Filtro opcional `?escola_id=`.
     *
     * GET /api/v2/atividades-recentes
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $query = AtividadeRecente::query();

        if (! $this->scope->isGlobalViewer($actor)) {
            $escolaIds = $this->scope->accessibleSchoolIds($actor);
            $nucleoIds = $this->scope->accessibleNucleoIds($actor);

            $query->where(function (Builder $scoped) use ($escolaIds, $nucleoIds, $actor): void {
                $scoped
                    ->whereIn('escola_id', $escolaIds)
                    ->orWhereIn('nucleo_id', $nucleoIds)
                    ->orWhere('ator_id', $actor->id);
            });
        }

        if (($escolaId = $request->query('escola_id')) !== null) {
            $query->where('escola_id', $escolaId);
        }

        $atividades = $query->orderByDesc('created_at')->paginate(20);

        return $this->paginatedResponse($atividades, AtividadeResource::class);
    }
}
