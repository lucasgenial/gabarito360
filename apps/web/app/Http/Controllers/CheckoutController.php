<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $planoId = $request->query('plano_id');

        $resposta = $this->api->get('/v1/planos');
        $planos   = collect($resposta['data'] ?? []);
        $plano    = $planoId ? $planos->firstWhere('id', (int) $planoId) : null;

        return view('checkout.index', compact('planos', 'plano'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'plano_id' => 'required|integer',
            'licencas' => 'nullable|integer|min:1|max:10',
        ]);

        $resposta = $this->api->post('/v1/assinaturas/checkout', [
            'plano_id' => (int) $request->input('plano_id'),
            'licencas' => (int) ($request->input('licencas', 1)),
        ]);

        if (isset($resposta['data']['init_point'])) {
            return redirect()->away($resposta['data']['init_point']);
        }

        return back()->withErrors(['checkout' => $resposta['message'] ?? 'Não foi possível iniciar o pagamento.']);
    }

    public function sucesso(Request $request): View
    {
        return view('checkout.sucesso', [
            'status'        => $request->query('status', 'approved'),
            'payment_id'    => $request->query('payment_id'),
            'collection_id' => $request->query('collection_id'),
        ]);
    }

    public function falha(): View
    {
        return view('checkout.falha');
    }

    public function pendente(): View
    {
        return view('checkout.pendente');
    }
}
