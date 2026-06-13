<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileDeviceIsActive
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = $request->user()?->currentAccessToken();

        if (
            ! $accessToken instanceof PersonalAccessToken
            || ! $accessToken->can('mobile')
            || $accessToken->can('api')
        ) {
            return $next($request);
        }

        $device = $accessToken->dispositivoMobile()
            ->where('usuario_id', $request->user()->id)
            ->first();

        if ($device === null || $device->isRevoked()) {
            $this->audit->record(
                action: AuditAction::ACCESS_BLOCKED_DEVICE,
                entityType: 'dispositivo_mobile',
                entityId: $device?->id,
                actorUserId: $request->user()->id,
                metadata: ['motivo' => $device === null ? 'dispositivo_inexistente' : 'dispositivo_revogado'],
            );

            $accessToken->delete();

            throw new AuthenticationException;
        }

        if (! $device->supportsCurrentAppVersion() && ! $request->routeIs('api.v2.auth.logout')) {
            $this->audit->record(
                action: AuditAction::ACCESS_BLOCKED_VERSION,
                entityType: 'dispositivo_mobile',
                entityId: $device->id,
                actorUserId: $request->user()->id,
                metadata: [
                    'versao_app' => $device->versao_app,
                    'versao_minima' => config('gabarito360.mobile.minimum_app_version'),
                ],
            );

            return ApiResponse::error(
                code: 'APP_VERSION_UNSUPPORTED',
                message: 'Atualize o aplicativo para continuar.',
                errors: [
                    'minimum_version' => [(string) config('gabarito360.mobile.minimum_app_version')],
                ],
                status: 426,
            );
        }

        $device->markAccessed();

        return $next($request);
    }
}
