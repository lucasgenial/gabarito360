<?php

namespace App\Http\Controllers\Api\V2\Escolas;

use App\Enums\StatusEnum;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Escolas\EscolaPerfilPermissoesRequest;
use App\Http\Resources\Api\V2\PerfilPublicoResource;
use App\Models\Escola;
use App\Models\Perfil;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Authorization\UserAdministrationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EscolaPerfilController extends BaseApiController
{
    public function index(Request $request, Escola $escola, UserAdministrationScope $scope): JsonResponse
    {
        abort_unless($scope->canAccessSchool($this->actor($request), $escola), 403);

        $perfis = Perfil::query()
            ->where('sistema', true)
            ->where('status', StatusEnum::ACTIVE->value)
            ->with('permissoes')
            ->withCount(['usuarioVinculos as membros_count' => function (Builder $query) use ($escola): void {
                $query->where('escola_id', $escola->id)
                    ->where('inicio_at', '<=', now())
                    ->whereNull('fim_at');
            }])
            ->orderBy('nome')
            ->get();

        return $this->successResponse(PerfilPublicoResource::collection($perfis));
    }

    public function updatePermissoes(
        EscolaPerfilPermissoesRequest $request,
        Escola $escola,
        string $perfil,
        UserAdministrationScope $scope,
        AuditService $audit,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($scope->canAccessSchool($actor, $escola), 403);

        $perfilModel = Perfil::query()
            ->where('codigo', $perfil)
            ->where('sistema', true)
            ->with('permissoes')
            ->firstOrFail();

        $audit->record(
            action: AuditAction::SCHOOL_PROFILE_PERMISSIONS_ATTEMPTED,
            entityType: 'perfil',
            entityId: $perfilModel->id,
            actorUserId: $actor->id,
            metadata: ['escola_id' => $escola->id, 'perfil' => $perfil],
            nucleoId: $escola->nucleo_id,
            escolaId: $escola->id,
        );

        $atuais = $perfilModel->permissoes->pluck('codigo');

        // Permissões dos perfis de sistema são fixas (ADR-D011). Reafirmar o
        // estado atual é aceito (200); qualquer tentativa de alterar uma
        // permissão fixa é rejeitada (403).
        foreach ($request->input('permissoes') as $permissao) {
            $estaAtribuida = $atuais->contains($permissao['chave']);

            if ((bool) $permissao['permitido'] !== $estaAtribuida) {
                abort(403, 'Permissoes deste perfil sao fixas e nao podem ser alteradas.');
            }
        }

        return $this->successResponse(null, 200);
    }
}
