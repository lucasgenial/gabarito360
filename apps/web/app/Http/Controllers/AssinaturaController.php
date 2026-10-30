<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssinaturaController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(): View
    {
        $resposta    = $this->api->get('/v1/assinaturas/minha');
        $assinatura  = $resposta['data'] ?? null;

        return view('assinatura.index', compact('assinatura'));
    }

    public function cancelar(): RedirectResponse
    {
        $this->api->post('/v1/assinaturas/cancelar', []);

        return redirect()->route('assinatura.index')
            ->with('sucesso', 'Assinatura cancelada com sucesso.');
    }
}
