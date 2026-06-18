<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        $usuario->update(['ultimo_acesso' => now()]);

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

    public function registro(Request $request): JsonResponse
    {
        $request->validate([
            'nome'   => 'required|string|max:200',
            'email'  => 'required|email|unique:usuarios,email',
            'cpf'    => 'required|string|regex:/^\d{11}$/',
            'perfil' => 'required|in:coordenador,professor,dir_escolar,dir_nucleo',
            'termos' => 'required|accepted',
        ], [
            'cpf.regex'        => 'O CPF deve conter 11 dígitos numéricos.',
            'email.unique'     => 'Este e-mail já está cadastrado.',
            'termos.required'  => 'É necessário aceitar os termos de uso e a LGPD.',
            'termos.accepted'  => 'É necessário aceitar os termos de uso e a LGPD.',
        ]);

        Usuario::create([
            'nome'     => $request->nome,
            'email'    => $request->email,
            'cpf'      => $request->cpf,
            'perfil'   => $request->perfil,
            'password' => Hash::make(Str::random(32)),
            'ativo'    => false,
        ]);

        return ApiResponse::success(
            ['mensagem' => 'Cadastro realizado. Aguarde a aprovação do administrador.'],
            null,
            201
        );
    }

    public function esqueceuSenha(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $usuario = Usuario::where('email', $request->email)->first();

        if ($usuario) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->upsert(
                ['email' => $request->email, 'token' => Hash::make($token), 'created_at' => now()],
                ['email'],
                ['token', 'created_at']
            );
        }

        // Sempre retorna sucesso por segurança (evita enumeração de e-mails)
        return ApiResponse::success([
            'mensagem' => 'Se o e-mail estiver cadastrado, um link de recuperação foi enviado.',
        ]);
    }

    public function redefinirSenha(Request $request): JsonResponse
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return ApiResponse::error('Token inválido ou expirado.', 422);
        }

        if (now()->diffInHours($record->created_at) >= 24) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return ApiResponse::error('Link expirado. Solicite um novo link de recuperação.', 422);
        }

        Usuario::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return ApiResponse::success(['mensagem' => 'Senha redefinida com sucesso.']);
    }
}
