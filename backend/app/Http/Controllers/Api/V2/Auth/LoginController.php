<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Auth\LoginRequest;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Auth\SessionTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends BaseApiController
{
    public function __construct(
        private AuditService $audit,
        private SessionTracker $sessions,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if (
            $user === null
            || ! Hash::check($credentials['senha'], $user->password)
            || $user->status !== UserStatus::ACTIVE
        ) {
            $inativo = $user !== null && $user->status !== UserStatus::ACTIVE;

            $this->audit->record(
                action: $inativo ? AuditAction::LOGIN_BLOCKED_USER : AuditAction::LOGIN_FAILED,
                entityType: 'usuario',
                entityId: $user?->id,
                actorUserId: $user?->id,
                metadata: ['motivo' => $inativo ? 'usuario_inativo_ou_bloqueado' : 'credenciais_invalidas'],
            );

            $this->sessions->record($user?->id, $inativo ? 'login_bloqueado' : 'login_falho', $request);

            return $this->errorResponse(
                code: 'INVALID_CREDENTIALS',
                message: 'Credenciais invalidas.',
                status: 401,
            );
        }

        $manterConectado = (bool) ($credentials['manter_conectado'] ?? false);

        $payload = DB::transaction(function () use ($user, $manterConectado, $request): array {
            $user->forceFill(['ultimo_acesso_at' => now()])->save();

            $expiresAt = $manterConectado
                ? now()->addDays(max(1, (int) config('gabarito360.auth.web_token_remember_days')))
                : now()->addHours(max(1, (int) config('gabarito360.auth.web_token_hours')));

            $token = $user->createToken('api', ['api'], $expiresAt);
            $sessao = $this->sessions->start($user, $token->accessToken, $request, $manterConectado);

            $this->audit->record(
                action: AuditAction::LOGIN_SUCCEEDED,
                entityType: 'usuario',
                entityId: $user->id,
                actorUserId: $user->id,
                metadata: ['cliente' => 'api'],
            );

            return ['sessao' => $sessao, 'token' => $token->plainTextToken];
        });

        $sessao = $payload['sessao'];

        return $this->successResponse([
            'id' => $sessao->id,
            'token' => $payload['token'],
            'dispositivo' => $sessao->dispositivo,
            'criado_em' => $sessao->criado_em?->toAtomString(),
            'ultimo_acesso' => $sessao->ultimo_acesso_at?->toAtomString(),
        ], 200);
    }
}
