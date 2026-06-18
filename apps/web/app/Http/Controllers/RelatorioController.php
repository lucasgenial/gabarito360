<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function prova(int $id): View
    {
        $resp = $this->api->get("/v1/relatorios/prova/{$id}");

        if ($resp->failed()) {
            abort($resp->status() === 404 ? 404 : 422, $resp->json('message') ?? 'Relatório não encontrado.');
        }

        return view('relatorios.prova', $resp->json('data'));
    }

    public function turmaProva(int $turmaId, int $provaId): View
    {
        $resp = $this->api->get("/v1/relatorios/turma/{$turmaId}/prova/{$provaId}");

        if ($resp->failed()) {
            abort($resp->status() === 404 ? 404 : 422, $resp->json('message') ?? 'Relatório não encontrado.');
        }

        return view('relatorios.turma-prova', $resp->json('data'));
    }
}
