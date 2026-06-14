<?php

namespace App\Http\Controllers\Api\V2\Me;

use App\Actions\Account\UpdateOwnPasswordAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Me\UpdatePasswordRequest;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Auth\SessionTracker;
use Symfony\Component\HttpFoundation\Response;

class UpdatePasswordController extends BaseApiController
{
    public function __construct(
        private UpdateOwnPasswordAction $action,
        private SessionTracker $sessions,
        private AuditService $audit,
    ) {}

    public function __invoke(UpdatePasswordRequest $request): Response
    {
        $user = $request->user();

        // Action troca a senha e revoga todos os tokens da conta.
        $this->action->execute($user, $request->validated()['senha']);
        $this->sessions->endAll($user);

        $this->audit->record(
            action: AuditAction::PASSWORD_CHANGED,
            entityType: 'usuario',
            entityId: $user->id,
            actorUserId: $user->id,
        );

        return response()->noContent();
    }
}
