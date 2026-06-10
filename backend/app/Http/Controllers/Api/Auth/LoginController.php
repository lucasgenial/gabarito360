<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\UserStatus;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends AuthController
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

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

        $user->forceFill(['ultimo_acesso_at' => now()])->save();

        $token = $user->createToken(
            name: $credentials['token_name'] ?? 'api',
            abilities: ['api'],
        );

        return $this->successResponse([
            'user' => UserResource::make($this->loadAuthorizedContext($user)),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }
}
