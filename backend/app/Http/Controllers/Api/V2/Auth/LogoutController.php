<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\PersonalAccessToken;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Auth\SessionTracker;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutController extends BaseApiController
{
    public function __construct(
        private AuditService $audit,
        private SessionTracker $sessions,
    ) {}

    public function __invoke(Request $request): Response
    {
        $accessToken = $request->user()?->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $this->sessions->endForToken($accessToken->id, $request);

            $this->audit->record(
                action: AuditAction::LOGOUT,
                entityType: 'usuario',
                entityId: $request->user()->id,
                actorUserId: $request->user()->id,
                metadata: ['cliente' => 'api'],
            );

            $accessToken->delete();
        }

        return response()->noContent();
    }
}
