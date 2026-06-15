<?php

namespace App\Http\Controllers\Api\V2\Auditorias;

use App\Enums\PermissionCode;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\AuditoriaResource;
use App\Models\Auditoria;
use App\Services\Authorization\PortalScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaController extends BaseApiController
{
    public function __construct(private PortalScope $scope) {}

    /**
     * Consulta a trilha de auditoria. Admin de configurações vê tudo; gestor de
     * usuários vê o próprio escopo (núcleo/escola) e as próprias ações.
     * Filtros: `?acao=`, `?entidade_tipo=`, `?usuario_id=`, `?de=`, `?ate=`.
     *
     * GET /api/v2/auditorias
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $admin = $this->scope->hasAnyPermission($actor, PermissionCode::MANAGE_SETTINGS);
        $gestor = $this->scope->hasAnyPermission($actor, PermissionCode::MANAGE_USERS_PROFILES_LINKS);

        abort_unless($admin || $gestor, 403);

        $query = Auditoria::query();

        if (! $admin) {
            $escolaIds = $this->scope->accessibleSchoolIds($actor);
            $nucleoIds = $this->scope->accessibleNucleoIds($actor);

            $query->where(function (Builder $scoped) use ($escolaIds, $nucleoIds, $actor): void {
                $scoped
                    ->whereIn('escola_id', $escolaIds)
                    ->orWhereIn('nucleo_id', $nucleoIds)
                    ->orWhere('usuario_id', $actor->id);
            });
        }

        foreach (['acao' => 'acao', 'entidade_tipo' => 'entidade_tipo', 'usuario_id' => 'usuario_id'] as $param => $column) {
            if (($value = $request->query($param)) !== null && $value !== '') {
                $query->where($column, $value);
            }
        }
        if (($de = $request->query('de')) !== null) {
            $query->where('created_at', '>=', $de);
        }
        if (($ate = $request->query('ate')) !== null) {
            $query->where('created_at', '<=', $ate);
        }

        $auditorias = $query->orderByDesc('created_at')->paginate(50);

        return $this->paginatedResponse($auditorias, AuditoriaResource::class);
    }
}
