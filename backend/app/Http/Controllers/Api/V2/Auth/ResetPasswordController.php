<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends BaseApiController
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $status = Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['senha'],
                'token' => $data['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
                $user->sessoes()->ativas()->update(['encerrado_at' => now()]);

                event(new PasswordReset($user));

                $this->audit->record(
                    action: AuditAction::PASSWORD_RESET,
                    entityType: 'usuario',
                    entityId: $user->id,
                    actorUserId: $user->id,
                );
            },
        );

        if ($status === Password::PasswordReset) {
            return $this->successResponse(null, 200);
        }

        return $this->errorResponse(
            code: 'VALIDATION_ERROR',
            message: 'Nao foi possivel redefinir a senha.',
            errors: ['email' => [__($status)]],
            status: 422,
        );
    }
}
