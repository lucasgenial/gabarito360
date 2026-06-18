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
            return redirect()->route('painel');
        }

        return view('auth.login', [
            'tab' => $request->query('tab', 'login'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $response = $this->api->post('/auth/login', [
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        if ($response->failed()) {
            return back()
                ->withInput($request->only('email'))
                ->with('erro', 'E-mail ou senha incorretos.');
        }

        $data = $response->json('data');

        session([
            'auth_token'  => $data['token'],
            'auth_perfil' => $data['perfil'],
            'auth_nome'   => $data['nome'],
            'auth_email'  => $data['email'],
        ]);

        return redirect()->route('painel');
    }

    public function cadastro(Request $request)
    {
        $request->validate([
            'nome'   => 'required|string|max:200',
            'email'  => 'required|email',
            'cpf'    => 'required|string',
            'perfil' => 'required|string',
            'termos' => 'required|accepted',
        ], [
            'termos.required' => 'Você precisa aceitar os termos de uso e a LGPD.',
            'termos.accepted' => 'Você precisa aceitar os termos de uso e a LGPD.',
        ]);

        $cpfLimpo = preg_replace('/\D/', '', $request->cpf);

        $resp = $this->api->post('/auth/registro', [
            'nome'   => $request->nome,
            'email'  => $request->email,
            'cpf'    => $cpfLimpo,
            'perfil' => $request->perfil,
            'termos' => true,
        ]);

        if ($resp->failed()) {
            $msg = $resp->json('message') ?? 'Erro ao criar conta. Verifique os dados e tente novamente.';
            return redirect()->route('login', ['tab' => 'signup'])
                ->withInput()
                ->with('erro_signup', $msg);
        }

        return redirect()->route('login', ['tab' => 'signup'])
            ->with('sucesso_signup', 'Cadastro realizado! Aguarde a aprovação do administrador.');
    }

    public function showEsqueciSenha()
    {
        return view('auth.esqueci-senha');
    }

    public function postEsqueciSenha(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $this->api->post('/auth/esqueci-senha', ['email' => $request->email]);

        return redirect()->route('esqueci-senha')
            ->with('sucesso', 'Se o e-mail estiver cadastrado, um link de recuperação foi enviado.');
    }

    public function showRedefinirSenha(Request $request)
    {
        return view('auth.redefinir-senha', [
            'token' => $request->query('token', ''),
            'email' => $request->query('email', ''),
        ]);
    }

    public function postRedefinirSenha(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ], [
            'password.min'       => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        $resp = $this->api->post('/auth/redefinir-senha', [
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
            ->with('sucesso', 'Senha redefinida com sucesso! Faça login com a nova senha.');
    }

    public function destroy(Request $request)
    {
        $this->api->post('/auth/logout');

        $request->session()->flush();

        return redirect()->route('login');
    }
}
