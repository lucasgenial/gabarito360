<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use App\Support\NavegacaoWeb;
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

        return view('correcao.show', [
            'prova'  => $resp->json('data'),
            'status' => ['correcao'=>$resp->json('data.correcao')??['fechada'=>!empty($resp->json('data.correcao_fechada_em'))]],
            'alunos' => [],
        ]);
    }

    public function alunos(int $provaId)
    {
        $resp=$this->api->get('/v1/provas/'.$provaId);abort_unless($resp->successful(),$resp->status());
        $alunos=[];
        foreach($resp->json('data.turmas')??[] as $t){
            $r=$this->api->get('/v1/alunos',['turma_id'=>$t['id']]);abort_unless($r->successful(),$r->status());
            foreach($r->json('data.alunos')??[] as $a)$alunos[$a['id']]=['id'=>$a['id'],'nome'=>$a['nome'],'matricula'=>$a['matricula']??null,'turma_nome'=>$t['nome']];
        }
        return response()->json(['alunos'=>array_values($alunos)]);
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
                $resp->failed() ? ['erro' => $resp->json('message') ?? 'Não foi possível enviar o cartão.'] : $this->decorarRecursos($resp->json('data') ?? [], $provaId),
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

        return response()->json($resp->failed() ? ['erro' => $resp->json('message') ?? 'Não foi possível acompanhar o envio.'] : $this->decorarRecursos($resp->json('data') ?? [], $provaId), $resp->status());
    }

    public function repetirEnvio(int $provaId, string $chave)
    {
        $resp = $this->api->post("/v1/provas/{$provaId}/cartoes/envios/{$chave}/repetir");

        return response()->json($resp->failed() ? ['erro' => $resp->json('message') ?? 'Não foi possível repetir a leitura.'] : $this->decorarRecursos($resp->json('data') ?? [], $provaId), $resp->status());
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

        return response()->json($resp->failed() ? ['cartoes' => []] : $this->decorarRecursos($resp->json('data') ?? [], $provaId), $resp->failed() ? $resp->status() : 200);
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
    public function excluir(Request $request,int $cartaoId)
    {
        $d=$request->validate(['motivo'=>'required|string|min:10|max:500','versao'=>'required|string|size:64']);
        $resp = $this->api->delete("/v1/cartoes/{$cartaoId}",$d);

        return response()->json(
            $resp->failed() ? ['erro' => $resp->json('message') ?? 'Não foi possível excluir.'] : $resp->json('data'),
            $resp->failed() ? $resp->status() : 200
        );
    }

    public function prepararRetirada(int $cartaoId)
    {
        $resp=$this->api->get("/v1/cartoes/{$cartaoId}");
        return response()->json($resp->failed()?['erro'=>$resp->json('message')]:array_intersect_key($resp->json('data')??[],array_flip(['id','versao_correcao','nota_atual'])),$resp->status());
    }

    public function vincularAluno(Request $request, int $cartaoId)
    {
        $request->validate(['aluno_id' => 'required|integer']);
        $resp = $this->api->put("/v1/cartoes/{$cartaoId}/vincular-aluno", $request->only('aluno_id'));

        return response()->json(
            $resp->failed() ? ['erro' => $resp->json('message') ?? 'Não foi possível vincular o aluno.'] : $this->decorarRecursos($resp->json('data') ?? []),
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

        return view('correcao.revisao', ['prova' => $prova->json('data'), 'itens' => $this->decorarRecursos($fila->json('data.itens') ?? [], $provaId)]);
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
    /** Decora exclusivamente o contrato BFF para os consumidores; a API permanece independente. */
    private function decorarRecursos(array $dados, ?int $provaId = null, bool $cartao = false): array
    {
        foreach ($dados as $campo => $valor) {
            if (is_array($valor)) $dados[$campo] = $this->decorarRecursos($valor, $provaId, $campo === 'cartoes' || ($cartao && is_int($campo)));
        }
        $id = $dados['cartao_id'] ?? ($cartao || isset($dados['processamento'], $dados['id']) || array_key_exists('acertos', $dados) ? ($dados['id'] ?? null) : null);
        if (is_numeric($id) && (int)$id > 0) {
            $r = [];
            foreach (['retirada','excluir','vincular-aluno','revisar','resolver-ambiguidade'] as $acao) $r[$acao] = NavegacaoWeb::destino('cartoes.'.$acao, ['id'=>(int)$id]);
            $r['imagem'] = NavegacaoWeb::destino('cartoes.imagem', ['id'=>(int)$id]);
            if ($provaId) {
                $r['revisao'] = NavegacaoWeb::destino('correcao.revisao', ['id'=>$provaId,'cartao'=>(int)$id]);
                if (!empty($dados['aluno_id'])) $r['resultado'] = NavegacaoWeb::destino('resultados.show', [$dados['aluno_id'],$provaId,'from'=>'correcao']);
            }
            $dados['_recursos'] = $r;
            foreach (['foto_url'=>'original','miniatura_url'=>'miniatura','endireitada_url'=>'endireitada'] as $campo=>$tipo) {
                if (!empty($dados[$campo])) $dados[$campo] = NavegacaoWeb::destino('cartoes.imagem-segura', ['id'=>(int)$id,'tipo'=>$tipo]);
            }
        }
        if (is_string($dados['tentativa_id'] ?? null)) $dados['_recursos']['tentativa'] = NavegacaoWeb::destino('correcao.tentativa', ['id'=>$dados['tentativa_id']]);
        return $dados;
    }

}
