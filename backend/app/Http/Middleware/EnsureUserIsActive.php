<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->trashed() || $user->status !== UserStatus::ACTIVE) {
            $accessToken = $user?->currentAccessToken();

            if ($accessToken instanceof PersonalAccessToken) {
                $accessToken->delete();
            }

            throw new AuthenticationException;
        }

        return $next($request);
    }
}
