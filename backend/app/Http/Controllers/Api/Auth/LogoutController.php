<?php

namespace App\Http\Controllers\Api\Auth;

use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutController extends AuthController
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $accessToken = $request->user()?->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $this->audit->record(
                action: AuditAction::LOGOUT,
                entityType: 'usuario',
                entityId: $request->user()->id,
                actorUserId: $request->user()->id,
                metadata: [
                    'cliente' => $accessToken->dispositivo_mobile_id === null ? 'api' : 'mobile',
                    'dispositivo_id' => $accessToken->dispositivo_mobile_id,
                ],
            );

            $accessToken->delete();
        }

        return $this->successResponse();
    }
}
