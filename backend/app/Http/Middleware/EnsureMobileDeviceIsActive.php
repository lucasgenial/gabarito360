<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileDeviceIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = $request->user()?->currentAccessToken();

        if (! $accessToken instanceof PersonalAccessToken || $accessToken->dispositivo_mobile_id === null) {
            return $next($request);
        }

        $device = $accessToken->dispositivoMobile()
            ->where('usuario_id', $request->user()->id)
            ->first();

        if ($device === null || $device->isRevoked()) {
            $accessToken->delete();

            throw new AuthenticationException;
        }

        if (! $device->supportsCurrentAppVersion() && ! $request->routeIs('api.v1.auth.logout')) {
            return ApiResponse::error(
                code: 'APP_VERSION_UNSUPPORTED',
                message: 'Atualize o aplicativo para continuar.',
                details: [
                    'minimum_version' => config('gabarito360.mobile.minimum_app_version'),
                ],
                status: 426,
            );
        }

        $device->markAccessed();

        return $next($request);
    }
}
