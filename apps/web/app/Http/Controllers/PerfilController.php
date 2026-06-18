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
            return back()->with('erro', $resp->json('message') ?? 'Erro ao atualizar dados.');
        }

        session(['auth_nome' => $request->input('nome')]);

        return back()->with('sucesso', 'Dados atualizados com sucesso.');
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
            return back()->with('erro', $resp->json('message') ?? 'Erro ao alterar senha.');
        }

        return back()->with('sucesso', 'Senha alterada com sucesso.');
    }
}
