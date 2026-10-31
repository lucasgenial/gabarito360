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
        // Campo invisível: pessoas não preenchem, robôs sim
        if ($request->filled('site')) {
            return redirect()->route('leads.sucesso');
        }

        $dados = $request->validate([
            'plano_id'    => 'nullable|integer',
            'nome'        => 'required|string|max:200',
            'email'       => 'required|email|max:150',
            'assunto'     => 'required|string|max:120',
            'telefone'    => 'nullable|string|max:20',
            'instituicao' => 'nullable|string|max:200',
            'cargo'       => 'nullable|string|max:100',
            'mensagem'    => 'required|string|max:2000',
            'cf-turnstile-response' => config('services.turnstile.enabled') ? 'required|string|max:2048' : 'nullable|string|max:2048',
        ]);

        $resposta = $this->api->post('/v1/leads', $dados);

        // Sem isto a mensagem se perde em silêncio e a pessoa vê "enviado"
        if ($resposta->failed()) {
            return back()->withInput($request->except('cf-turnstile-response'))->withErrors(['lead' => $resposta->json('message') ?? 'Não foi possível enviar a mensagem. Tente de novo.']);
        }

        return redirect()->route('leads.sucesso');
    }

    public function sucesso(): View
    {
        return view('leads.sucesso');
    }
}
