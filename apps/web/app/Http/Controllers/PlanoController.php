<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\View\View;

class PlanoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(): View
    {
        try {
            $resposta = $this->api->get('/v1/planos');
            $planos = $resposta->successful() ? $resposta->json('data', []) : [];
        } catch (\Illuminate\Http\Client\ConnectionException) {
            $planos = [];
        }
        $planos = array_values(array_filter($planos, fn ($p) => $p['ativo'] ?? true));
        $indisponivel = empty($planos);

        $individuais     = array_values(array_filter($planos, fn ($p) => $p['publico'] === 'individual' && $p['modo'] === 'autoatendimento'));
        $institucional   = array_values(array_filter($planos, fn ($p) => $p['publico'] === 'institucional' && $p['modo'] === 'autoatendimento'));
        $comerciais      = array_values(array_filter($planos, fn ($p) => $p['modo'] === 'comercial'));

        return view('planos.index', compact('individuais', 'institucional', 'comerciais', 'indisponivel'));
    }
}
