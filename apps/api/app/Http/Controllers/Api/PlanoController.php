<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Plano;

class PlanoController extends Controller
{
    public function index()
    {
        $planos = Plano::where('ativo', true)->get();

        return ApiResponse::success($planos->map(fn ($p) => [
            'id'                => $p->id,
            'nome'              => $p->nome,
            'publico'           => $p->publico,
            'modo'              => $p->modo,
            'preco_fixo'        => $p->preco_fixo,
            'preco_por_licenca' => $p->preco_por_licenca,
            'licencas_min'      => $p->licencas_min,
            'licencas_max'      => $p->licencas_max,
            'periodicidade'     => $p->periodicidade,
            'limites'           => $p->limites,
        ]));
    }
}
