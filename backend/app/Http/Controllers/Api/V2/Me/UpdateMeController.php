<?php

namespace App\Http\Controllers\Api\V2\Me;

use App\Actions\Account\UpdateOwnProfileAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Me\UpdateMeRequest;
use App\Http\Resources\Api\V2\UsuarioResource;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;

class UpdateMeController extends BaseApiController
{
    public function __construct(
        private UpdateOwnProfileAction $action,
        private AuditService $audit,
    ) {}

    public function __invoke(UpdateMeRequest $request): JsonResponse
    {
        $user = $request->user();
        $before = $user->only(['nome', 'email', 'telefone']);

        $user = $this->action->execute($user, $request->validated());

        $this->audit->record(
            action: AuditAction::USER_UPDATED,
            entityType: 'usuario',
            entityId: $user->id,
            actorUserId: $user->id,
            before: $before,
            after: $user->only(['nome', 'email', 'telefone']),
        );

        return $this->successResponse(UsuarioResource::make($user));
    }
}
