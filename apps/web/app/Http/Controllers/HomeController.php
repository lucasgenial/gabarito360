<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\View\View;

class HomeController extends Controller
{
    // Fallback estático usado quando a API não está disponível
    private const PLANOS_FALLBACK = [
        [
            'id' => null, 'publico' => 'individual', 'modo' => 'autoatendimento',
            'nome' => 'Professor Individual — Mensal',
            'preco_fixo' => '9.99', 'preco_por_licenca' => null,
            'licencas_min' => 1, 'licencas_max' => 1, 'periodicidade' => 'mensal', 'ativo' => true,
        ],
        [
            'id' => null, 'publico' => 'individual', 'modo' => 'autoatendimento',
            'nome' => 'Professor Individual — Anual',
            'preco_fixo' => '99.90', 'preco_por_licenca' => null,
            'licencas_min' => 1, 'licencas_max' => 1, 'periodicidade' => 'anual', 'ativo' => true,
        ],
        [
            'id' => null, 'publico' => 'institucional', 'modo' => 'autoatendimento',
            'nome' => 'Instituição — até 10 licenças',
            'preco_fixo' => null, 'preco_por_licenca' => '7.50',
            'licencas_min' => 1, 'licencas_max' => 10, 'periodicidade' => 'mensal', 'ativo' => true,
        ],
    ];

    public function __construct(private ApiClient $api) {}

    public function index(): View
    {
        try {
            $resposta = $this->api->get('/planos');
            $planos   = collect($resposta['data'] ?? [])
                ->filter(fn ($p) => $p['modo'] === 'autoatendimento' && $p['ativo'])
                ->values()
                ->all();
        } catch (\Throwable) {
            $planos = self::PLANOS_FALLBACK;
        }

        if (empty($planos)) {
            $planos = self::PLANOS_FALLBACK;
        }

        return view('home.index', compact('planos'));
    }
}
