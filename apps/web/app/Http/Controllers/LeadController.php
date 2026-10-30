<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $planoId = $request->query('plano_id');

        $resposta = $this->api->get('/v1/planos');
        $planos   = collect($resposta['data'] ?? [])
            ->filter(fn ($p) => $p['modo'] === 'comercial')
            ->values();

        $plano = $planoId ? $planos->firstWhere('id', (int) $planoId) : null;

        return view('leads.fale-conosco', compact('planos', 'plano'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'plano_id'           => 'nullable|integer',
            'nome'               => 'required|string|max:200',
            'email'              => 'required|email|max:150',
            'telefone'           => 'nullable|string|max:20',
            'instituicao'        => 'nullable|string|max:200',
            'cargo'              => 'nullable|string|max:100',
            'licencas_estimadas' => 'nullable|integer|min:1',
            'mensagem'           => 'nullable|string|max:2000',
        ]);

        $resposta = $this->api->post('/v1/leads', $request->only([
            'plano_id', 'nome', 'email', 'telefone',
            'instituicao', 'cargo', 'licencas_estimadas', 'mensagem',
        ]));

        // Sem isto a mensagem se perde em silêncio e a pessoa vê "enviado"
        if ($resposta->failed()) {
            return back()->withInput()->withErrors(['lead' => $resposta->json('message') ?? 'Não foi possível enviar agora. Tente de novo.']);
        }

        return redirect()->route('leads.sucesso');
    }

    public function sucesso(): View
    {
        return view('leads.sucesso');
    }
}
