<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $usuario = Usuario::where('email', $request->email)
            ->where('ativo', true)
            ->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            return ApiResponse::unauthorized('Credenciais inválidas.');
        }

        $token = $usuario->createToken('api-token')->plainTextToken;

        return ApiResponse::success([
            'token'  => $token,
            'perfil' => $usuario->perfil,
            'nome'   => $usuario->nome,
            'email'  => $usuario->email,
        ], null, 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(['message' => 'Sessão encerrada com sucesso.']);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success($request->user());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nome'     => 'sometimes|string|max:200',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $request->user()->update($data);

        return ApiResponse::success($request->user()->fresh());
    }
}
