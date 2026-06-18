<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguracaoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(): View
    {
        $resp = $this->api->get('/v1/configuracoes');

        if ($resp->failed()) {
            abort($resp->status() === 403 ? 403 : 422, $resp->json('message') ?? 'Erro ao carregar configurações.');
        }

        return view('configuracoes.index', ['config' => $resp->json('data')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'meta_media'       => 'required|numeric|min:0|max:10',
            'meta_minima'      => 'required|numeric|min:0|max:10',
            'limiar_seges_min' => 'required|integer|min:1|max:1440',
        ]);

        $resp = $this->api->put('/v1/configuracoes', $request->only('meta_media', 'meta_minima', 'limiar_seges_min'));

        if ($resp->failed()) {
            return back()->withInput()->with('erro', $resp->json('message') ?? 'Erro ao salvar configurações.');
        }

        return back()->with('sucesso', 'Configurações atualizadas com sucesso.');
    }
}
