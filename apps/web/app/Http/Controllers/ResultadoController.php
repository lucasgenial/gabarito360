<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultadoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function show(Request $request, int $alunoId, int $provaId): View
    {
        $resp = $this->api->get("/v1/alunos/{$alunoId}/resultados/{$provaId}");

        if ($resp->failed()) {
            abort($resp->status() === 404 ? 404 : 422, $resp->json('message') ?? 'Resultado não encontrado.');
        }

        return view('resultados.show', [
            'dados' => $resp->json('data'),
            'from'  => $request->query('from'),
            'turma' => $request->query('turma'),
        ]);
    }
}
