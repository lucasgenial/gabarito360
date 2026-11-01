<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguracaoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $request->validate(['rede_id' => 'sometimes|integer|min:1']);
        $opcoes = $this->api->get('/v1/configuracoes', ['listar_redes' => 1]);
        $redes = $opcoes->json('data.redes') ?? [];
        if (!$request->filled('rede_id') && count($redes) > 1) return view('configuracoes.index', ['config' => null, 'redes' => $redes]);
        $resp = $this->api->get('/v1/configuracoes', $request->only('rede_id'));

        if ($resp->failed()) {
            abort($resp->status() === 403 ? 403 : 422, $resp->json('message') ?? 'Não foi possível carregar as configurações.');
        }

        return view('configuracoes.index', ['config' => $resp->json('data'), 'redes' => $redes]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'rede_id' => 'required|integer|min:1',
            'meta_media'       => 'required|numeric|min:0|max:10',
            'meta_minima'      => 'required|numeric|min:0|max:10',
            'limiar_seges_min' => 'required|integer|min:1|max:1440',
        ]);

        $resp = $this->api->put('/v1/configuracoes', $request->only('rede_id', 'meta_media', 'meta_minima', 'limiar_seges_min'));

        if ($resp->failed()) {
            return back()->withInput()->with('erro', $resp->json('message') ?? 'Não foi possível salvar as configurações.');
        }

        return redirect()->route('configuracoes.index', ['rede_id' => $request->integer('rede_id')])->with('sucesso', 'Configurações atualizadas.');
    }
}
