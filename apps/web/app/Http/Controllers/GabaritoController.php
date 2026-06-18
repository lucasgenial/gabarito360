<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GabaritoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function edit(int $provaId): View
    {
        $resp = $this->api->get("/v1/provas/{$provaId}");
        if ($resp->failed()) {
            abort(404, 'Prova não encontrada.');
        }

        $prova      = $resp->json('data');
        $gabResp    = $this->api->get("/v1/provas/{$provaId}/gabarito");
        $questoes   = $gabResp->successful() ? ($gabResp->json('data.questoes') ?? []) : [];
        $publicado  = $gabResp->successful() ? (bool) $gabResp->json('data.publicado') : false;

        return view('provas.gabarito-edit', [
            'prova'     => $prova,
            'questoes'  => $questoes,
            'publicado' => $publicado,
        ]);
    }

    public function salvar(Request $request, int $provaId)
    {
        $questoes = [];
        foreach ($request->input('alternativas', []) as $num => $alt) {
            if ($alt) {
                $questoes[] = [
                    'numero_questao' => (int) $num,
                    'alternativa'    => $alt,
                ];
            }
        }

        $resp = $this->api->post("/v1/provas/{$provaId}/gabarito", ['questoes' => $questoes]);

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Erro ao salvar gabarito.');
        }

        return back()->with('sucesso', 'Gabarito salvo com sucesso.');
    }

    public function publicar(int $provaId)
    {
        $resp = $this->api->post("/v1/provas/{$provaId}/gabarito/publicar");

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível publicar o gabarito.');
        }

        return redirect()->route('provas.gabarito.show', $provaId)
            ->with('sucesso', 'Gabarito publicado com sucesso.');
    }

    public function show(int $provaId): View
    {
        $resp = $this->api->get("/v1/provas/{$provaId}");
        if ($resp->failed()) {
            abort(404, 'Prova não encontrada.');
        }

        $prova    = $resp->json('data');
        $gabResp  = $this->api->get("/v1/provas/{$provaId}/gabarito");
        $questoes = $gabResp->successful() ? ($gabResp->json('data.questoes') ?? []) : [];
        $gabarito = $gabResp->successful() ? $gabResp->json('data.gabarito') : null;

        return view('provas.gabarito', [
            'prova'    => $prova,
            'gabarito' => $gabarito,
            'questoes' => $questoes,
        ]);
    }
}
