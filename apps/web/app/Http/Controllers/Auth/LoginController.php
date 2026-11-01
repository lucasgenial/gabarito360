<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(Request $request)
    {
        if (session('auth_token')) {
            return redirect()->route($request->boolean('retorno_convite') ? 'vinculos.convite' : 'painel');
        }

        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $response = $this->api->post('/v1/auth/login', [
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        if ($response->failed()) {
            return back()
                ->withInput($request->only('email'))
                ->with('erro', 'E-mail ou senha incorretos.');
        }

        $data = $response->json('data');
        $request->session()->regenerate();

        session([
            'auth_token'   => $data['token'],
            'auth_vinculos' => $data['selecionar_vinculo'] ?? false,
            'auth_internos' => $data['selecionar_acesso_interno'] ?? false,
            'auth_perfil'  => $data['perfil'],
            'auth_nome'    => $data['nome'],
            'auth_email'   => $data['email'],
            'auth_titular' => $data['eh_titular'] ?? false,
            'auth_permissoes' => $data['permissoes'] ?? null,
            'auth_trocar_senha' => $data['trocar_senha'] ?? false,
        ]);

        if ($request->boolean('retorno_convite')) return redirect()->route(($data['trocar_senha'] ?? false) ? 'trocar-senha' : 'vinculos.convite', ['retorno_convite' => 1]);
        return redirect()->route('painel');
    }

    public function showEsqueciSenha()
    {
        return view('auth.esqueci-senha');
    }

    public function postEsqueciSenha(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $this->api->post('/v1/auth/esqueci-senha', ['email' => $request->email]);

        return redirect()->route('esqueci-senha')
            ->with('sucesso', 'Se o e-mail estiver cadastrado, um link de recuperação foi enviado.');
    }

    public function showConvite(Request $request)
    {
        $token = (string) $request->query('token', '');
        $email = (string) $request->query('conta', $request->query('email', ''));
        $resp  = $this->api->post('/v1/auth/convite', ['token' => $token, 'email' => $email]);

        return view('auth.convite', [
            'token'   => $token,
            'email'   => $email,
            'convite' => $resp->successful() ? $resp->json('data') : null,
        ]);
    }

    public function recusarConvite(Request $request)
    {
        $dados = $request->validate(['email' => 'required|string|max:255', 'token' => 'required|string']);
        $resp  = $this->api->post('/v1/auth/convite/recusar', $dados);

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível recusar o convite.');
        }

        return redirect()->route('login')->with('sucesso', 'Convite recusado. Avisamos quem enviou.');
    }

    public function showRedefinirSenha(Request $request)
    {
        return view('auth.redefinir-senha', [
            'token' => $request->query('token', ''),
            'email' => $request->query('conta', $request->query('email', '')),
        ]);
    }

    public function postRedefinirSenha(Request $request)
    {
        $request->validate([
            'email'                 => 'required|string|max:255',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ], [
            'password.min'       => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        $resp = $this->api->post('/v1/auth/redefinir-senha', [
            'email'                 => $request->email,
            'token'                 => $request->token,
            'password'              => $request->password,
            'password_confirmation' => $request->password_confirmation,
        ]);

        if ($resp->failed()) {
            $msg = $resp->json('message') ?? 'Token inválido ou expirado.';
            return back()->withInput(['email' => $request->email])->with('erro', $msg);
        }

        return redirect()->route('login')
            ->with('sucesso', 'Senha salva. Entre com a nova senha.');
    }

    public function destroy(Request $request)
    {
        try { $this->api->identidade()->post('/v1/auth/logout'); }
        catch (\Illuminate\Http\Client\ConnectionException) { /* A saída local independe da disponibilidade da API. */ }
        finally {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
    }
}
