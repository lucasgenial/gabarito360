<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Mail\EmailModelo;
use App\Models\Assinatura;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const VALIDADE_LINK_MINUTOS = 60;

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

        $ehTitular = Assinatura::where('titular_id', $usuario->id)->exists();

        return ApiResponse::success([
            'token'      => $token,
            'perfil'     => $usuario->perfil,
            'permissoes' => \App\Support\Permissao::efetivas($usuario),
            'nome'       => $usuario->nome,
            'email'      => $usuario->email,
            'eh_titular' => $ehTitular,
        ], null, 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(['message' => 'Sessão encerrada com sucesso.']);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(array_merge($request->user()->toArray(), [
            'permissoes' => \App\Support\Permissao::efetivas($request->user()),
        ]));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nome'          => 'sometimes|string|max:200',
            'senha_atual'   => 'required_with:password|string',
            'password'      => 'sometimes|string|min:8|confirmed',
        ]);

        if (isset($data['password'])) {
            if (!Hash::check($data['senha_atual'], $request->user()->password)) {
                return ApiResponse::error('Senha atual incorreta.', [], 422);
            }
            $data['password'] = Hash::make($data['password']);
        }

        unset($data['senha_atual']);

        $request->user()->update($data);

        return ApiResponse::success($request->user()->fresh());
    }

    public function esqueceuSenha(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $usuario = Usuario::where('email', $request->email)->where('ativo', true)->first();

        if ($usuario) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->upsert(
                ['email' => $usuario->email, 'token' => Hash::make($token), 'created_at' => now()],
                ['email'],
                ['token', 'created_at']
            );

            $link = config('emails.url_web') . '/redefinir-senha?' . http_build_query(['token' => $token, 'email' => $usuario->email]);

            Mail::to($usuario->email)->queue(new EmailModelo('recuperar-senha', 'Redefina sua senha do Gabarito360', [
                'nome' => $usuario->nome,
                'link' => $link,
            ]));
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
            return ApiResponse::error('Token inválido ou expirado.', [], 422);
        }

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        if (Carbon::parse($record->created_at)->addMinutes(self::VALIDADE_LINK_MINUTOS)->isPast()) {
            return ApiResponse::error('Link expirado. Solicite um novo link de recuperação.', [], 422);
        }

        $usuario = Usuario::where('email', $request->email)->first();
        if (!$usuario) {
            return ApiResponse::error('Token inválido ou expirado.', [], 422);
        }

        $usuario->update(['password' => Hash::make($request->password)]);
        $usuario->tokens()->delete(); // encerra as sessões abertas com a senha antiga

        Mail::to($usuario->email)->queue(new EmailModelo('senha-alterada', 'Sua senha do Gabarito360 foi alterada', [
            'nome'   => $usuario->nome,
            'quando' => now()->timezone('America/Bahia')->format('d/m/Y \à\s H:i'),
        ]));

        return ApiResponse::success(['mensagem' => 'Senha redefinida.']);
    }
}
