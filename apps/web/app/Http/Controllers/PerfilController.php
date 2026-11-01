<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function show(): View
    {
        $resp = $this->api->get('/v1/auth/me');

        if ($resp->failed()) {
            abort(422, 'Não foi possível carregar seu perfil.');
        }

        return view('perfil.show', ['usuario' => $resp->json('data')]);
    }

    public function update(Request $request)
    {
        $request->validate(['nome' => 'required|string|max:200']);

        $resp = $this->api->put('/v1/auth/me', ['nome' => $request->input('nome')]);

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível atualizar os dados.');
        }

        session(['auth_nome' => $request->input('nome')]);

        return back()->with('sucesso', 'Dados atualizados.');
    }

    public function formTrocarSenha()
    {
        return view('auth.trocar-senha');
    }

    /** Troca obrigatória: exigida pelo gestor, antes de liberar o sistema. */
    public function trocarSenha(Request $request)
    {
        $request->validate([
            'senha_atual' => 'required|string',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        $resp = $this->api->put('/v1/auth/me', $request->only('senha_atual', 'password', 'password_confirmation'));

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível alterar a senha.');
        }

        session()->forget('auth_trocar_senha');

        return redirect()->route($request->boolean('retorno_convite') ? 'vinculos.convite' : 'painel')->with('sucesso', 'Senha alterada.');
    }

    public function senha(Request $request)
    {
        $request->validate([
            'senha_atual'          => 'required|string',
            'password'             => 'required|string|min:8|confirmed',
        ]);

        $resp = $this->api->put('/v1/auth/me', [
            'senha_atual'          => $request->input('senha_atual'),
            'password'             => $request->input('password'),
            'password_confirmation'=> $request->input('password_confirmation'),
        ]);

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível alterar a senha.');
        }

        return back()->with('sucesso', 'Senha alterada.');
    }

    public function email(Request $request)
    {
        $dados = $request->validate([
            'novo_email'  => 'required|email|max:190',
            'senha_atual' => 'required|string',
        ]);

        $resp = $this->api->post('/v1/auth/email', $dados);

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível pedir a troca de e-mail.');
        }

        return back()->with('sucesso', 'Enviamos um link de confirmação para o novo e-mail. O atual segue valendo até lá.');
    }

    /** Link do e-mail: funciona com ou sem sessão aberta. */
    public function confirmarEmail(Request $request)
    {
        $resp = $this->api->post('/v1/auth/confirmar-email', ['token' => (string) $request->query('token')]);
        $destino = session('auth_token') ? 'perfil' : 'login';

        if ($resp->failed()) {
            return redirect()->route($destino)->with('erro', $resp->json('message') ?? 'Link inválido ou expirado.');
        }

        return redirect()->route($destino)->with('sucesso', 'E-mail confirmado.');
    }
}
