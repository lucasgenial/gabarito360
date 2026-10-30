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

    /** PDF do resultado (gerado pela API) para baixar, imprimir ou enviar por WhatsApp. */
    public function pdf(int $alunoId, int $provaId)
    {
        $resp = $this->api->getArquivo("/v1/alunos/{$alunoId}/resultados/{$provaId}/pdf");
        if ($resp->failed() || !str_contains((string) $resp->header('Content-Type'), 'pdf')) {
            abort(404, $resp->json('message') ?? 'Não foi possível gerar o PDF.');
        }

        $nome = 'resultado-aluno-' . $alunoId . '-prova-' . $provaId . '.pdf';
        if (preg_match('/filename="?([^";]+)"?/', (string) $resp->header('Content-Disposition'), $m)) {
            $nome = $m[1];
        }

        return response($resp->body(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => (request()->boolean('baixar') ? 'attachment' : 'inline') . '; filename="' . $nome . '"',
        ]);
    }

}
