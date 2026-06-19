<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\View\View;

class PlanoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(): View
    {
        $resposta = $this->api->get('/planos');
        $planos   = $resposta['data'] ?? [];

        $individuais     = array_values(array_filter($planos, fn ($p) => $p['publico'] === 'individual' && $p['modo'] === 'autoatendimento'));
        $institucional   = array_values(array_filter($planos, fn ($p) => $p['publico'] === 'institucional' && $p['modo'] === 'autoatendimento'));
        $comerciais      = array_values(array_filter($planos, fn ($p) => $p['modo'] === 'comercial'));

        return view('planos.index', compact('individuais', 'institucional', 'comerciais'));
    }
}
