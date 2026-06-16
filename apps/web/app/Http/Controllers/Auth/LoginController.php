<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index()
    {
        if (session('auth_token')) {
            return redirect()->route('painel');
        }

        return view('auth.login');
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

    public function destroy(Request $request)
    {
        $this->api->post('/auth/logout');

        $request->session()->flush();

        return redirect()->route('login');
    }
}
