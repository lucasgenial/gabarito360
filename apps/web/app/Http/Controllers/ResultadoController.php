<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultadoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function lote(Request $request, int $provaId)
    {
        $dados = $request->validate(['turma_id' => 'nullable|integer|min:1', 'modelo' => 'nullable|in:aluno,professor']);
        $resp = $this->api->getArquivo("/v1/provas/{$provaId}/resultados/pdf", $dados);
        if ($resp->failed() || ! str_contains((string) $resp->header('Content-Type'), 'pdf')) {
            abort($resp->failed() ? $resp->status() : 502, $resp->json('message') ?? 'Não foi possível gerar os resultados.');
        }
        $nome = 'resultados-prova-'.$provaId.(! empty($dados['turma_id']) ? '-turma-'.(int) $dados['turma_id'] : '').'.pdf';

        return response($resp->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nome.'"',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function show(Request $request, int $alunoId, int $provaId): View
    {
        $filtro = $request->validate(['tentativas_pagina' => 'nullable|integer|min:1|max:1000000', 'tentativa' => 'nullable|uuid']);
        $resp = $this->api->get("/v1/alunos/{$alunoId}/resultados/{$provaId}");

        if ($resp->failed()) {
            abort($resp->status() === 404 ? 404 : 422, $resp->json('message') ?? 'Resultado não encontrado.');
        }

        if (! empty($filtro['tentativa'])) {
            $request->session()->keep(['errors', '_old_input']);
        }

        return view('resultados.show', [
            'dados' => $resp->json('data'),
            'from' => $request->query('from'),
            'turma' => $request->query('turma'),
        ]);
    }

    public function historico(Request $request, int $alunoId, int $provaId)
    {
        $filtro = $request->validate(['tentativas_pagina' => 'nullable|integer|min:1|max:1000000', 'tentativa' => 'nullable|uuid']);
        $historico = null;
        $tentativa = null;
        $erroTentativa = false;

        try {
            $lista = $this->api->get('/v1/provas/'.$provaId.'/tentativas', ['aluno_id' => $alunoId, 'page' => $filtro['tentativas_pagina'] ?? 1]);
            abort_unless($lista->successful(), $lista->status());
            $historico = $lista->json('data');
        } catch (ConnectionException) {
            abort(503, 'Histórico temporariamente indisponível.');
        }
        if (! empty($filtro['tentativa'])) {
            try {
                $detalhe = $this->api->get('/v1/tentativas/'.$filtro['tentativa']);
                abort_if(in_array($detalhe->status(), [403, 404], true), 404);
                if ($detalhe->successful()) {
                    $tentativa = $detalhe->json('data');
                    abort_unless((int) ($tentativa['aluno_id'] ?? 0) === $alunoId && (int) ($tentativa['prova']['id'] ?? 0) === $provaId, 404);
                } else {
                    $erroTentativa = true;
                }
            } catch (ConnectionException) {
                $erroTentativa = true;
            }
        }

        return response()->json(['html' => view('resultados._historico_conteudo', [
            'historico' => $historico, 'tentativa' => $tentativa, 'erroTentativa' => $erroTentativa,
            'aluno' => ['id' => $alunoId], 'prova' => ['id' => $provaId],
        ])->render(), 'total' => $historico['total']]);
    }

    /** PDF do resultado (gerado pela API) para baixar, imprimir ou enviar por WhatsApp. */
    public function pdf(Request $request, int $alunoId, int $provaId)
    {
        $d = $request->validate(['modelo' => 'nullable|in:aluno,professor']);
        $resp = $this->api->getArquivo("/v1/alunos/{$alunoId}/resultados/{$provaId}/pdf", $d);
        if ($resp->failed() || ! str_contains((string) $resp->header('Content-Type'), 'pdf')) {
            abort($resp->failed() ? $resp->status() : 502, $resp->json('message') ?? 'Não foi possível gerar o PDF.');
        }

        $nome = 'resultado-aluno-'.$alunoId.'-prova-'.$provaId.'.pdf';
        if (preg_match('/filename="?([^";]+)"?/', (string) $resp->header('Content-Disposition'), $m)) {
            $nome = $m[1];
        }

        return response($resp->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => (request()->boolean('baixar') ? 'attachment' : 'inline').'; filename="'.$nome.'"',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
