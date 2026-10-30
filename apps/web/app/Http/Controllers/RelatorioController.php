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

    public function turma(int $turmaId): View
    {
        $resp = $this->api->get("/v1/relatorios/turma/{$turmaId}");
        if ($resp->failed()) {
            abort(404, $resp->json('message') ?? 'Relatório não encontrado.');
        }

        return view('relatorios.turma', $resp->json('data'));
    }

    /** Relatório consolidado de escola, núcleo ou rede (sem id: a unidade do usuário). */
    public function consolidado(\Illuminate\Http\Request $request, ?int $id = null): View
    {
        $nivel = $request->route()->defaults['nivel'] ?? $request->route('nivel');
        $resp  = $this->api->get("/v1/relatorios/{$nivel}" . ($id ? "/{$id}" : ''));
        if ($resp->failed()) {
            abort(404, $resp->json('message') ?? 'Relatório não encontrado.');
        }

        return view('relatorios.consolidado', $resp->json('data') + ['nivel' => $nivel]);
    }

    public function aluno(int $alunoId): View
    {
        $resp = $this->api->get("/v1/relatorios/aluno/{$alunoId}");
        if ($resp->failed()) {
            abort(404, $resp->json('message') ?? 'Boletim não encontrado.');
        }

        return view('relatorios.aluno', $resp->json('data'));
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
