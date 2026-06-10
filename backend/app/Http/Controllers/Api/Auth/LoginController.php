<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\UserStatus;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\DispositivoMobile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends AuthController
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();
        $deviceInput = $credentials['dispositivo'] ?? null;

        if (
            $deviceInput !== null
            && version_compare(
                $deviceInput['versao_app'],
                (string) config('gabarito360.mobile.minimum_app_version'),
                '<',
            )
        ) {
            return $this->errorResponse(
                code: 'APP_VERSION_UNSUPPORTED',
                message: 'Atualize o aplicativo para continuar.',
                details: [
                    'minimum_version' => config('gabarito360.mobile.minimum_app_version'),
                ],
                status: 426,
            );
        }

        if (
            $user === null
            || ! Hash::check($credentials['password'], $user->password)
            || $user->status !== UserStatus::ACTIVE
        ) {
            return $this->errorResponse(
                code: 'INVALID_CREDENTIALS',
                message: 'Credenciais invalidas.',
                status: 401,
            );
        }

        $result = DB::transaction(function () use ($credentials, $deviceInput, $user): array {
            $device = $deviceInput === null ? null : $this->resolveMobileDevice($user, $deviceInput);

            if ($device?->isRevoked()) {
                return ['device' => $device, 'token' => null];
            }

            $user->forceFill(['ultimo_acesso_at' => now()])->save();
            $device?->tokens()->delete();

            $token = $user->createToken(
                name: $device === null ? ($credentials['token_name'] ?? 'api') : 'mobile:'.$device->id,
                abilities: $device === null ? ['api'] : ['mobile'],
                expiresAt: $device === null
                    ? null
                    : now()->addDays(max(1, (int) config('gabarito360.mobile.token_expiration_days'))),
            );

            if ($device !== null) {
                $token->accessToken->forceFill(['dispositivo_mobile_id' => $device->id])->save();
            }

            return ['device' => $device, 'token' => $token];
        });

        if ($result['token'] === null) {
            return $this->errorResponse(
                code: 'DEVICE_REVOKED',
                message: 'Dispositivo mobile revogado.',
                status: 403,
            );
        }

        return $this->successResponse([
            'user' => UserResource::make($this->loadAuthorizedContext($user)),
            'token' => $result['token']->plainTextToken,
            'token_type' => 'Bearer',
            'dispositivo' => $result['device'] === null ? null : [
                'id' => $result['device']->id,
                'identificador' => $result['device']->identificador,
                'plataforma' => $result['device']->plataforma,
                'versao_app' => $result['device']->versao_app,
            ],
        ]);
    }

    /**
     * @param  array<string, string|null>  $deviceInput
     */
    private function resolveMobileDevice(User $user, array $deviceInput): DispositivoMobile
    {
        $device = $user->dispositivosMobile()
            ->where('identificador', $deviceInput['identificador'])
            ->lockForUpdate()
            ->first();

        if ($device?->isRevoked()) {
            return $device;
        }

        return $user->dispositivosMobile()->updateOrCreate(
            ['identificador' => $deviceInput['identificador']],
            [
                'plataforma' => $deviceInput['plataforma'],
                'modelo_dispositivo' => $deviceInput['modelo_dispositivo'] ?? null,
                'versao_sistema' => $deviceInput['versao_sistema'] ?? null,
                'versao_app' => $deviceInput['versao_app'],
                'ultimo_acesso_at' => now(),
            ],
        );
    }
}
