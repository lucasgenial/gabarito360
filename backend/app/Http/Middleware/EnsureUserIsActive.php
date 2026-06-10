<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->trashed() || $user->status !== UserStatus::ACTIVE) {
            if ($user instanceof User) {
                $this->audit->record(
                    action: AuditAction::ACCESS_BLOCKED_USER,
                    entityType: 'usuario',
                    entityId: $user->id,
                    actorUserId: $user->id,
                    metadata: ['motivo' => 'usuario_inativo_bloqueado_ou_excluido'],
                );
            }

            $accessToken = $user?->currentAccessToken();

            if ($accessToken instanceof PersonalAccessToken) {
                $accessToken->delete();
            }

            throw new AuthenticationException;
        }

        return $next($request);
    }
}
