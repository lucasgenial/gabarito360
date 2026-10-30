<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorrecaoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    /** "📷 Corrigir": provas aplicáveis do usuário, para escolher qual corrigir. */
    public function index(): View
    {
        $resp   = $this->api->get('/v1/provas');
        $provas = collect($resp->successful() ? ($resp->json('data.provas') ?? []) : [])
            ->filter(fn ($p) => in_array($p['status'], ['publicada', 'em_correcao', 'corrigida'], true))
            ->sortBy(fn ($p) => [$p['status'] === 'corrigida' ? 1 : 0, $p['disciplina']])
            ->values()
            ->all();

        $turmasResp = $this->api->get('/v1/turmas');
        $turmas = collect($turmasResp->successful() ? ($turmasResp->json('data.turmas') ?? []) : [])
            ->filter(fn ($t) => $t['ativo'] ?? true)->sortBy('nome')->values()->all();

        return view('correcao.index', ['provas' => $provas, 'turmas' => $turmas]);
    }

    /** Acompanhamento da correção por turma: provas × alunos (MP-038). */
    public function turma(int $turmaId): View
    {
        $resp = $this->api->get("/v1/turmas/{$turmaId}/correcao");
        if ($resp->failed()) {
            abort(404, 'Turma não encontrada.');
        }

        return view('correcao.turma', ['dados' => $resp->json('data')]);
    }

    public function show(int $provaId): View
    {
        $resp = $this->api->get("/v1/provas/{$provaId}");
        if ($resp->failed()) {
            abort(404, 'Prova não encontrada.');
        }

        // Aplicador só envia cartões (doc 03): tela só com a câmera, sem notas nem ambíguos.
        if (session('auth_perfil') === 'aplicador') {
            return view('correcao.camera', ['prova' => $resp->json('data'), 'alunos' => []]);
        }

        $statusResp = $this->api->get("/v1/provas/{$provaId}/cartoes/status");
        $status     = $statusResp->successful() ? $statusResp->json('data') : [
            'total' => 0, 'lidos' => 0, 'pendentes' => 0, 'ambiguos' => 0,
            'concluida' => false, 'ambiguidades' => [], 'recentes' => [],
        ];

        $turmas = $resp->json('data.turmas') ?? [];
        // Alunos de todas as turmas da prova (para vincular cartões sem QR/em branco)
        $alunos = [];
        foreach ($turmas as $t) {
            $alunosResp = $this->api->get('/v1/alunos', ['turma_id' => $t['id']]);
            foreach ($alunosResp->successful() ? ($alunosResp->json('data.alunos') ?? []) : [] as $a) {
                $a['turma_nome'] = $t['nome'];
                $alunos[] = $a;
            }
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

        if ($request->expectsJson()) {
            return response()->json(
                $resp->failed() ? ['erro' => $resp->json('message') ?? 'Erro ao enviar cartão.'] : $resp->json('data'),
                $resp->failed() ? 422 : 200
            );
        }

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Erro ao enviar cartão.');
        }

        return back()->with('sucesso', 'Cartão enviado para processamento.');
    }

    /** Alunos corrigidos (JSON para a tela de correção). */
    public function cartoes(int $provaId)
    {
        $resp = $this->api->get("/v1/provas/{$provaId}/cartoes");

        return response()->json($resp->failed() ? ['cartoes' => []] : $resp->json('data'), $resp->failed() ? $resp->status() : 200);
    }

    /** Foto do cartão, repassada da API (não fica em URL pública). */
    public function imagem(int $cartaoId)
    {
        $resp = $this->api->getArquivo("/v1/cartoes/{$cartaoId}/imagem");
        if ($resp->failed() || !str_starts_with((string) $resp->header('Content-Type'), 'image/')) {
            abort(404);
        }

        return response($resp->body(), 200, [
            'Content-Type'  => $resp->header('Content-Type'),
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    /** Exclui a correção do aluno para fotografar de novo. */
    public function excluir(int $cartaoId)
    {
        $resp = $this->api->delete("/v1/cartoes/{$cartaoId}");

        return response()->json(
            $resp->failed() ? ['erro' => $resp->json('message') ?? 'Não foi possível excluir.'] : $resp->json('data'),
            $resp->failed() ? 422 : 200
        );
    }

    public function vincularAluno(Request $request, int $cartaoId)
    {
        $request->validate(['aluno_id' => 'required|integer']);
        $resp = $this->api->put("/v1/cartoes/{$cartaoId}/vincular-aluno", $request->only('aluno_id'));

        return response()->json(
            $resp->failed() ? ['erro' => $resp->json('message') ?? 'Erro ao vincular aluno.'] : $resp->json('data'),
            $resp->failed() ? 422 : 200
        );
    }

    /** Baixa o PDF dos cartões-resposta (gerado pela API) para impressão. */
    public function pdf(Request $request, int $provaId)
    {
        $resp = $this->api->getArquivo("/v1/provas/{$provaId}/cartoes/pdf", array_filter([
            'turma_id' => $request->query('turma_id'),
            'brancos'  => $request->query('brancos', 0),
        ], fn ($v) => $v !== null && $v !== ''));

        if ($resp->failed() || !str_contains((string) $resp->header('Content-Type'), 'pdf')) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível gerar os cartões.');
        }

        $nome = 'cartoes-prova-' . $provaId . ($request->query('turma_id') ? '-turma-' . $request->query('turma_id') : '') . '.pdf';

        return response($resp->body(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nome . '"',
        ]);
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
