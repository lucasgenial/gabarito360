<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorrecaoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function show(int $provaId): View
    {
        $resp = $this->api->get("/v1/provas/{$provaId}");
        if ($resp->failed()) {
            abort(404, 'Prova não encontrada.');
        }

        $statusResp = $this->api->get("/v1/provas/{$provaId}/cartoes/status");
        $status     = $statusResp->successful() ? $statusResp->json('data') : [
            'total' => 0, 'lidos' => 0, 'pendentes' => 0, 'ambiguos' => 0,
            'concluida' => false, 'ambiguidades' => [], 'recentes' => [],
        ];

        $turmas = $resp->json('data.turmas') ?? [];
        $alunos = [];
        if (count($turmas) > 0) {
            $alunosResp = $this->api->get('/v1/alunos', ['turma_id' => $turmas[0]['id']]);
            $alunos     = $alunosResp->successful() ? ($alunosResp->json('data.alunos') ?? []) : [];
        }

        return view('correcao.show', [
            'prova'  => $resp->json('data'),
            'status' => $status,
            'alunos' => $alunos,
        ]);
    }

    public function status(int $provaId)
    {
        $statusResp = $this->api->get("/v1/provas/{$provaId}/cartoes/status");

        if ($statusResp->failed()) {
            return response()->json(['erro' => true], 422);
        }

        return response()->json($statusResp->json('data'));
    }

    public function upload(Request $request, int $provaId)
    {
        $request->validate([
            'imagem'   => 'required|image|max:10240',
            'aluno_id' => 'sometimes|integer|nullable',
        ]);

        $resp = $this->api->postFile(
            "/v1/provas/{$provaId}/cartoes",
            'imagem',
            $request->file('imagem'),
            $request->filled('aluno_id') ? ['aluno_id' => $request->input('aluno_id')] : []
        );

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Erro ao enviar cartão.');
        }

        return back()->with('sucesso', 'Cartão enviado para processamento.');
    }

    public function resolverAmbiguidade(Request $request, int $cartaoId)
    {
        $request->validate([
            'numero_questao' => 'required|integer',
            'alternativa'    => 'required|string',
        ]);

        $resp = $this->api->post("/v1/cartoes/{$cartaoId}/resolver-ambiguidade", $request->only('numero_questao', 'alternativa'));

        if ($resp->failed()) {
            return response()->json(['erro' => $resp->json('message') ?? 'Erro ao resolver ambiguidade.'], 422);
        }

        return response()->json($resp->json('data'));
    }

    public function revisar(Request $request, int $cartaoId)
    {
        $request->validate([
            'numero_questao' => 'required|integer',
            'alternativa'    => 'required|string',
        ]);

        $resp = $this->api->post("/v1/cartoes/{$cartaoId}/revisar", $request->only('numero_questao', 'alternativa'));

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Erro ao revisar leitura.');
        }

        return back()->with('sucesso', 'Leitura revisada com sucesso.');
    }
}
