<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SenhaTrocada;
use App\Http\Responses\ApiResponse;
use App\Mail\EmailModelo;
use App\Services\ContaUsuario;
use App\Models\Assinatura;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

        // O token de quem precisa trocar a senha só abre a troca (ver SenhaTrocada).
        $nomeToken = $usuario->forcar_troca_senha ? SenhaTrocada::TOKEN_RESTRITO : 'api-token';
        $token = $usuario->createToken($nomeToken)->plainTextToken;

        $ehTitular = Assinatura::where('titular_id', $usuario->id)->exists();

        return ApiResponse::success([
            'token'      => $token,
            'perfil'     => $usuario->perfil,
            'permissoes' => \App\Support\Permissao::efetivas($usuario),
            'nome'       => $usuario->nome,
            'email'      => $usuario->email,
            'eh_titular' => $ehTitular,
            'trocar_senha' => (bool) $usuario->forcar_troca_senha,
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
            if ($request->user()->forcar_troca_senha && Hash::check($data['password'], $request->user()->password)) {
                return ApiResponse::error('Escolha uma senha diferente da atual.', [], 422);
            }
            $data['password'] = Hash::make($data['password']);
            $data['forcar_troca_senha'] = false;
        }

        unset($data['senha_atual']);

        $request->user()->update($data);

        return ApiResponse::success($request->user()->fresh());
    }

    /** Pede a troca de e-mail: o endereço atual segue valendo até a confirmação. */
    public function trocarEmail(Request $request, ContaUsuario $conta): JsonResponse
    {
        $data = $request->validate([
            'novo_email'  => 'required|email|max:190|unique:usuarios,email',
            'senha_atual' => 'required|string',
        ], ['novo_email.unique' => 'Este e-mail já está em uso.']);

        if (!Hash::check($data['senha_atual'], $request->user()->password)) {
            return ApiResponse::error('Senha atual incorreta.', [], 422);
        }

        $conta->pedirTrocaDeEmail($request->user(), $data['novo_email']);

        return ApiResponse::success(['mensagem' => 'Enviamos um link de confirmação para o novo e-mail.']);
    }

    public function confirmarEmail(Request $request, ContaUsuario $conta): JsonResponse
    {
        $data = $request->validate(['token' => 'required|string']);

        if (!$conta->confirmarTrocaDeEmail($data['token'])) {
            return ApiResponse::error('Link inválido ou expirado. Peça a troca de e-mail novamente.', [], 422);
        }

        return ApiResponse::success(['mensagem' => 'E-mail confirmado.']);
    }

    /** Mostra ao convidado a função e o vínculo antes de aceitar. */
    public function consultarConvite(Request $request, ContaUsuario $conta): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email', 'token' => 'required|string']);

        $usuario = Usuario::where('email', $data['email'])->first();
        $convite = $conta->convitePendente($data['email'], $data['token']);
        if (!$usuario || !$convite) {
            return ApiResponse::error('Convite inválido ou vencido.', [], 422);
        }

        return ApiResponse::success($conta->dadosDoConvite($usuario, Usuario::find($convite->convidado_por)));
    }

    public function recusarConvite(Request $request, ContaUsuario $conta): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email', 'token' => 'required|string']);

        $usuario = Usuario::where('email', $data['email'])->first();
        $convite = $conta->convitePendente($data['email'], $data['token']);
        if (!$usuario || !$convite) {
            return ApiResponse::error('Convite inválido ou vencido.', [], 422);
        }

        $conta->recusarConvite($usuario, $convite);

        return ApiResponse::success(['mensagem' => 'Convite recusado.']);
    }

    public function esqueceuSenha(Request $request, ContaUsuario $conta): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $usuario = Usuario::where('email', $request->email)->where('ativo', true)->first();

        if ($usuario) {
            $conta->enviarLinkDeSenha($usuario);
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

        $expira = $record->expira_em ? Carbon::parse($record->expira_em) : Carbon::parse($record->created_at)->addMinutes(self::VALIDADE_LINK_MINUTOS);
        if ($expira->isPast()) {
            return ApiResponse::error('Link expirado. Solicite um novo link de recuperação.', [], 422);
        }

        $usuario = Usuario::where('email', $request->email)->first();
        if (!$usuario) {
            return ApiResponse::error('Token inválido ou expirado.', [], 422);
        }

        $usuario->update(['password' => Hash::make($request->password), 'forcar_troca_senha' => false]);
        $usuario->tokens()->delete(); // encerra as sessões abertas com a senha antiga

        if ($record->expira_em) { // convite: é a primeira senha, não uma troca
            return ApiResponse::success(['mensagem' => 'Senha criada.']);
        }

        Mail::to($usuario->email)->queue(new EmailModelo('senha-alterada', 'Sua senha do Gabarito360 foi alterada', [
            'nome'   => $usuario->nome,
            'quando' => now()->timezone('America/Bahia')->format('d/m/Y \à\s H:i'),
        ]));

        return ApiResponse::success(['mensagem' => 'Senha redefinida.']);
    }
}
