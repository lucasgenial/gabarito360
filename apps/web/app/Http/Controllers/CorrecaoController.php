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
        if (\App\Support\Acesso::perfil() === 'aplicador') {
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
            'imagem' => 'required|image|max:10240',
            'aluno_id' => 'sometimes|integer|nullable',
            'envio_id' => 'sometimes|uuid',
        ]);

        $resp = $this->api->postFile(
            "/v1/provas/{$provaId}/cartoes",
            'imagem',
            $request->file('imagem'),
            $request->only('aluno_id', 'envio_id'),
            timeout: 15
        );

        if ($request->expectsJson()) {
            return response()->json(
                $resp->failed() ? ['erro' => $resp->json('message') ?? 'Não foi possível enviar o cartão.'] : $resp->json('data'),
                $resp->status()
            );
        }

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível enviar o cartão.');
        }

        return back()->with('cartao_envio', $resp->json('data.envio_id'))
            ->with('sucesso', 'Foto salva. Acompanhe o resultado na lista de envios.');
    }

    public function envio(int $provaId, string $chave)
    {
        $resp = $this->api->get("/v1/provas/{$provaId}/cartoes/envios/{$chave}");

        return response()->json($resp->failed() ? ['erro' => $resp->json('message') ?? 'Não foi possível acompanhar o envio.'] : $resp->json('data'), $resp->status());
    }

    public function repetirEnvio(int $provaId, string $chave)
    {
        $resp = $this->api->post("/v1/provas/{$provaId}/cartoes/envios/{$chave}/repetir");

        return response()->json($resp->failed() ? ['erro' => $resp->json('message') ?? 'Não foi possível repetir a leitura.'] : $resp->json('data'), $resp->status());
    }

    public function fechar(int $provaId)
    {
        $resp = $this->api->post("/v1/provas/{$provaId}/correcao/fechar");

        return back()->with($resp->failed() ? 'erro' : 'sucesso', $resp->failed()
            ? ($resp->json('message') ?? 'Não foi possível fechar a correção.')
            : 'Correção fechada. Agora só a coordenação/direção altera ou reabre.');
    }

    public function reabrir(int $provaId)
    {
        $resp = $this->api->post("/v1/provas/{$provaId}/correcao/reabrir");

        return back()->with($resp->failed() ? 'erro' : 'sucesso', $resp->failed()
            ? ($resp->json('message') ?? 'Não foi possível reabrir a correção.')
            : 'Correção reaberta. Professores e corretores podem enviar, excluir e alterar de novo.');
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
        $resp = $this->api->get("/v1/cartoes/{$cartaoId}/imagem");
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
            $resp->failed() ? ['erro' => $resp->json('message') ?? 'Não foi possível vincular o aluno.'] : $resp->json('data'),
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

    /** Tela de revisão: uma questão por vez, da fila da prova ou de um cartão (?cartao=). */
    public function revisao(Request $request, int $provaId): View
    {
        $prova = $this->api->get("/v1/provas/{$provaId}");
        if ($prova->failed()) {
            abort(404, 'Prova não encontrada.');
        }

        $fila = $this->api->get("/v1/provas/{$provaId}/revisao", array_filter(['cartao' => $request->integer('cartao')]));

        return view('correcao.revisao', ['prova' => $prova->json('data'), 'itens' => $fila->json('data.itens') ?? []]);
    }

    public function resolverAmbiguidade(Request $request, int $cartaoId)
    {
        $request->validate([
            'numero_questao' => 'required|integer',
            'alternativa'    => 'required|string',
        ]);

        $resp = $this->api->post("/v1/cartoes/{$cartaoId}/resolver-ambiguidade", $request->only('numero_questao', 'alternativa'));

        if ($resp->failed()) {
            return response()->json(['erro' => $resp->json('message') ?? 'Não foi possível salvar a resposta.'], 422);
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

        if ($request->expectsJson()) {
            return $resp->failed()
                ? response()->json(['erro' => $resp->json('message') ?? 'Não foi possível revisar a leitura.'], 422)
                : response()->json($resp->json('data'));
        }

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível revisar a leitura.');
        }

        return back()->with('sucesso', 'Leitura revisada.');
    }
}
