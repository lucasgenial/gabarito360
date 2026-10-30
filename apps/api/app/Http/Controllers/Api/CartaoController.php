<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Jobs\ProcessarCartaoJob;
use App\Models\Cartao;
use App\Models\Prova;
use App\Services\Omr\AtualizadorStatusProva;
use App\Services\Omr\CalculadoraNota;
use App\Services\Omr\ImagensCartao;
use App\Services\Omr\GeradorCartoes;
use App\Support\Correcao;
use App\Support\Escopo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CartaoController extends Controller
{
    /**
     * Prova dentro do escopo do usuário. O aplicador só pode ENVIAR cartões
     * (doc 03, †APLIC): nas demais ações a prova não é encontrada para ele.
     */
    private function encontrarProva(Request $request, int $provaId, bool $envio = false): ?Prova
    {
        if ($request->user()->perfil === 'aplicador' && !$envio) {
            return null;
        }

        return Escopo::prova($request->user(), $provaId);
    }

    public function index(Request $request, int $provaId, ImagensCartao $imagens): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        // Lista para a tela de correção: aluno, turma, nota e se há foto (a foto vem por /cartoes/{id}/imagem)
        $cartoes = DB::table('cartoes')
            ->leftJoin('alunos', 'alunos.id', '=', 'cartoes.aluno_id')
            ->leftJoin('turmas', 'turmas.id', '=', 'alunos.turma_id')
            ->leftJoin('notas', 'notas.cartao_id', '=', 'cartoes.id')
            ->where('cartoes.prova_id', $provaId)
            ->orderBy('alunos.nome')
            ->orderByDesc('cartoes.id')
            ->get([
                'cartoes.id', 'cartoes.status', 'cartoes.aluno_id', 'cartoes.lingua', 'cartoes.created_at',
                'cartoes.imagem_url', 'alunos.nome as aluno_nome', 'alunos.matricula', 'turmas.nome as turma',
                'notas.acertos', 'notas.total_questoes', 'notas.nota_final',
            ])
            ->map(function ($c) use ($imagens) {
                $c->tem_imagem = (bool) $c->imagem_url;
                $c->foto_url = $c->imagem_url ? $imagens->url($c->id, 'original') : null;
                $c->miniatura_url = Storage::disk('public')->exists(ImagensCartao::caminho($c->id, 'miniatura'))
                    ? $imagens->url($c->id, 'miniatura')
                    : null;
                unset($c->imagem_url);
                $c->ambiguas = DB::table('cartao_respostas')->where('cartao_id', $c->id)->where('ambigua', true)->count();
                return $c;
            });

        return ApiResponse::success(['cartoes' => $cartoes]);
    }

    /** Foto do cartão (dado de aluno: só para quem acessa a prova; nunca por URL pública). */
    public function imagem(Request $request, int $id)
    {
        $cartao = Cartao::find($id);
        if (!$cartao || !$cartao->imagem_url || !$this->encontrarProva($request, $cartao->prova_id)) {
            return ApiResponse::notFound('Imagem não encontrada.');
        }

        $caminho = Storage::disk('public')->path($cartao->imagem_url);
        if (!is_file($caminho)) {
            return ApiResponse::notFound('Imagem não encontrada.');
        }

        return response()->file($caminho, ['Cache-Control' => 'private, max-age=600']);
    }

    /** Foto por link assinado: a assinatura já prova o acesso e a resposta não ocupa a web. */
    public function arquivo(int $id, string $tipo)
    {
        $cartao = Cartao::find($id);
        $relativo = $tipo === 'original' ? $cartao?->imagem_url : ImagensCartao::caminho($id, $tipo);
        $caminho = $cartao && $relativo ? Storage::disk('public')->path($relativo) : null;
        if (!$caminho || !is_file($caminho)) {
            return ApiResponse::notFound('Imagem não encontrada.');
        }

        return response()->file($caminho, ['Cache-Control' => 'private, max-age=3600']);
    }

    /** Exclui a correção (cartão, respostas, nota e foto) para o aluno ser fotografado de novo. */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $cartao = Cartao::find($id);
        $prova  = $cartao ? $this->encontrarProva($request, $cartao->prova_id) : null;
        if (!$cartao || !$prova) {
            return ApiResponse::notFound('Cartão não encontrado.');
        }
        if ($bloqueio = Correcao::bloqueio($request->user(), $prova)) {
            return ApiResponse::error($bloqueio, ['motivo' => 'correcao_fechada'], 403);
        }

        if ($cartao->imagem_url) {
            Storage::disk('public')->delete($cartao->imagem_url);
        }
        $cartao->delete(); // respostas, nota e logs de ambiguidade saem em cascata

        AtualizadorStatusProva::atualizar($prova->fresh());

        return ApiResponse::success(['excluido' => true]);
    }

    public function status(Request $request, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $base = DB::table('cartoes')->where('prova_id', $provaId);

        $total     = (clone $base)->count();
        $lidos     = (clone $base)->where('status', 'lido')->count();
        $ambiguos  = (clone $base)->where('status', 'ambiguo')->count();
        $pendentes = $total - $lidos - $ambiguos;

        $ambiguosLista = DB::table('cartoes')
            ->join('cartao_respostas', 'cartao_respostas.cartao_id', '=', 'cartoes.id')
            ->where('cartoes.prova_id', $provaId)
            ->where('cartoes.status', 'ambiguo')
            ->where('cartao_respostas.ambigua', true)
            ->select(
                'cartoes.id as cartao_id',
                'cartao_respostas.id as resposta_id',
                'cartao_respostas.numero_questao',
                'cartao_respostas.alternativas_detectadas'
            )
            ->get()
            ->map(function ($r) {
                $r->alternativas_detectadas = json_decode($r->alternativas_detectadas, true) ?? [];
                return $r;
            });

        $recentes = DB::table('cartoes')
            ->join('notas', 'notas.cartao_id', '=', 'cartoes.id')
            ->leftJoin('alunos', 'alunos.id', '=', 'cartoes.aluno_id')
            ->where('cartoes.prova_id', $provaId)
            ->orderByDesc('notas.updated_at')
            ->limit(8)
            ->select('cartoes.id as cartao_id', 'alunos.nome as aluno_nome', 'notas.nota_final', 'notas.updated_at')
            ->get();

        return ApiResponse::success([
            'total'        => $total,
            'lidos'        => $lidos,
            'pendentes'    => $pendentes,
            'ambiguos'     => $ambiguos,
            'concluida'    => $prova->fresh()->status === 'corrigida',
            'esperados'    => DB::table('alunos')
                ->join('prova_turmas', 'prova_turmas.turma_id', '=', 'alunos.turma_id')
                ->where('prova_turmas.prova_id', $provaId)->where('alunos.ativo', true)->count(),
            'ambiguidades' => $ambiguosLista,
            'recentes'     => $recentes,
            'prova_status' => $prova->status,
            'correcao'     => Correcao::situacao($prova->fresh()),
        ]);
    }

    public function store(Request $request, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId, envio: true);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }
        if ($bloqueio = Correcao::bloqueio($request->user(), $prova)) {
            return ApiResponse::error($bloqueio, ['motivo' => 'correcao_fechada'], 403);
        }

        if (!in_array($prova->status, ['publicada', 'em_correcao', 'corrigida'], true)) {
            return ApiResponse::error('A prova precisa estar publicada para receber cartões.', [], 422);
        }

        $data = $request->validate([
            'imagem'   => 'required|image|max:10240',
            'aluno_id' => 'sometimes|integer|exists:alunos,id|nullable',
        ]);

        $caminho = $request->file('imagem')->store('cartoes/' . $provaId, 'public');

        $cartao = Cartao::create([
            'prova_id'   => $provaId,
            'aluno_id'   => $data['aluno_id'] ?? null,
            'imagem_url' => $caminho,
            'status'     => 'pendente',
        ]);

        ProcessarCartaoJob::dispatch($cartao->id);

        // Recusado pelo QR (cartão de outra prova que não pode recebê-lo): explica o motivo.
        if (!Cartao::whereKey($cartao->id)->exists()) {
            $motivo = \Illuminate\Support\Facades\Cache::pull(ProcessarCartaoJob::chaveRecusa($cartao->id))
                ?? 'Este cartão não pôde ser corrigido nesta prova.';
            return ApiResponse::error($motivo, ['motivo' => 'outra_prova'], 422);
        }

        // Com fila síncrona o cartão já foi lido: devolve o resultado para o celular.
        $cartao->refresh()->load('aluno:id,nome');

        // Aplicador não vê notas (doc 03): só a confirmação de leitura e o aluno.
        if ($request->user()->perfil === 'aplicador') {
            return ApiResponse::success([
                'id'         => $cartao->id,
                'status'     => $cartao->status === 'pendente' ? 'processando' : $cartao->status,
                'aluno_id'   => $cartao->aluno_id,
                'aluno_nome' => $cartao->aluno?->nome,
            ], null, 202);
        }

        $nota = DB::table('notas')->where('cartao_id', $cartao->id)->first();

        return ApiResponse::success([
            'id'         => $cartao->id,
            // Preenchido quando o QR indicou outra prova e o cartão foi corrigido nela
            'prova_corrigida' => (int) $cartao->prova_id !== $provaId
                ? DB::table('provas')->where('id', $cartao->prova_id)->value('disciplina') : null,
            'status'     => $cartao->status === 'pendente' ? 'processando' : $cartao->status,
            'aluno_id'   => $cartao->aluno_id,
            'aluno_nome' => $cartao->aluno?->nome,
            'lingua'     => $cartao->lingua,
            'acertos'    => $nota->acertos ?? null,
            'total'      => $nota->total_questoes ?? null,
            'nota_final' => $nota->nota_final ?? null,
            'ambiguas'   => DB::table('cartao_respostas')->where('cartao_id', $cartao->id)->where('ambigua', true)->count(),
        ], null, 202);
    }

    /**
     * PDF dos cartões-resposta nativos (um por aluno + brancos), com QR de identificação.
     * Exceção documentada à regra "API só JSON": é download de arquivo, não tela.
     */
    public function pdf(Request $request, int $provaId, GeradorCartoes $gerador)
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $data = $request->validate([
            'turma_id' => 'sometimes|integer|nullable',
            'brancos'  => 'sometimes|integer|min:0|max:200',
        ]);

        try {
            $arquivo = $gerador->gerar($prova, $data['turma_id'] ?? null, (int) ($data['brancos'] ?? 0));
        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error('Não foi possível gerar os cartões: ' . $e->getMessage(), [], 500);
        }

        $nome = 'cartoes-' . \Illuminate\Support\Str::slug($prova->titulo . ' ' . $prova->disciplina) . '.pdf';

        return response()->download($arquivo, $nome, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend();
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $cartao = Cartao::with('aluno:id,nome,matricula')->find($id);
        if (!$cartao || !$this->encontrarProva($request, $cartao->prova_id)) {
            return ApiResponse::notFound('Cartão não encontrado.');
        }

        $respostas = DB::table('cartao_respostas')
            ->where('cartao_id', $id)
            ->orderBy('numero_questao')
            ->get()
            ->map(function ($r) {
                $r->alternativas_detectadas = json_decode($r->alternativas_detectadas, true) ?? [];
                return $r;
            });

        return ApiResponse::success(array_merge($cartao->toArray(), [
            'aluno_nome' => $cartao->aluno?->nome,
            'respostas'  => $respostas,
        ]));
    }

    public function vincularAluno(Request $request, int $id): JsonResponse
    {
        $cartao = Cartao::find($id);
        if (!$cartao || !($prova = $this->encontrarProva($request, $cartao->prova_id))) {
            return ApiResponse::notFound('Cartão não encontrado.');
        }
        if ($bloqueio = Correcao::bloqueio($request->user(), $prova)) {
            return ApiResponse::error($bloqueio, ['motivo' => 'correcao_fechada'], 403);
        }

        $data = $request->validate([
            'aluno_id' => 'required|integer|exists:alunos,id',
        ]);

        $cartao->update(['aluno_id' => $data['aluno_id']]);

        if ($cartao->status === 'lido') {
            CalculadoraNota::calcular($cartao->fresh());
        }

        return ApiResponse::success(['vinculado' => true]);
    }

    public function resolverAmbiguidade(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $cartao = Cartao::find($id);
        if (!$cartao || !($prova = $this->encontrarProva($request, $cartao->prova_id))) {
            return ApiResponse::notFound('Cartão não encontrado.');
        }
        if ($bloqueio = Correcao::bloqueio($usuario, $prova)) {
            return ApiResponse::error($bloqueio, ['motivo' => 'correcao_fechada'], 403);
        }

        $data = $request->validate([
            'numero_questao' => 'required|integer|min:1',
            'alternativa'    => 'required|string|in:A,B,C,D,E',
        ]);

        $resposta = DB::table('cartao_respostas')
            ->where('cartao_id', $id)
            ->where('numero_questao', $data['numero_questao'])
            ->first();

        if (!$resposta) {
            return ApiResponse::notFound('Questão não encontrada no cartão.');
        }

        DB::table('cartao_respostas')->where('id', $resposta->id)->update([
            'alternativa' => $data['alternativa'],
            'ambigua'     => false,
        ]);

        DB::table('ambiguidade_logs')->insert([
            'cartao_id'          => $cartao->id,
            'cartao_resposta_id' => $resposta->id,
            'usuario_id'         => $usuario->id,
            'alternativa_escolhida' => $data['alternativa'],
            'created_at'         => now(),
        ]);

        $restamAmbiguas = DB::table('cartao_respostas')
            ->where('cartao_id', $cartao->id)
            ->where('ambigua', true)
            ->exists();

        if (!$restamAmbiguas) {
            $cartao->update([
                'status'        => 'lido',
                'resolvido_por' => $usuario->id,
                'resolvido_em'  => now(),
            ]);

            CalculadoraNota::calcular($cartao->fresh());
            AtualizadorStatusProva::atualizar($cartao->prova);
        }

        return ApiResponse::success([
            'resolvido'      => true,
            'cartao_status'  => $cartao->fresh()->status,
        ]);
    }

    public function revisar(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $cartao = Cartao::find($id);
        if (!$cartao || !($prova = $this->encontrarProva($request, $cartao->prova_id))) {
            return ApiResponse::notFound('Cartão não encontrado.');
        }
        if ($bloqueio = Correcao::bloqueio($usuario, $prova)) {
            return ApiResponse::error($bloqueio, ['motivo' => 'correcao_fechada'], 403);
        }

        if ($cartao->status !== 'lido') {
            return ApiResponse::error('Apenas cartões já lidos podem ser revisados.', [], 422);
        }

        $data = $request->validate([
            'numero_questao' => 'required|integer|min:1',
            'alternativa'    => 'required|string|in:A,B,C,D,E',
        ]);

        $resposta = DB::table('cartao_respostas')
            ->where('cartao_id', $id)
            ->where('numero_questao', $data['numero_questao'])
            ->first();

        if (!$resposta) {
            return ApiResponse::notFound('Questão não encontrada no cartão.');
        }

        DB::table('cartao_respostas')->where('id', $resposta->id)->update([
            'alternativa' => $data['alternativa'],
        ]);

        $cartao->update([
            'revisado_por' => $usuario->id,
            'revisado_em'  => now(),
        ]);

        CalculadoraNota::calcular($cartao->fresh());

        return ApiResponse::success(['revisado' => true]);
    }

    /** Fecha a correção: a partir daqui só a supervisão altera, exclui ou inclui cartões. */
    public function fecharCorrecao(Request $request, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }
        if (!Correcao::fechada($prova)) {
            $prova->update(['correcao_fechada_em' => now(), 'correcao_fechada_por' => $request->user()->id]);
        }

        return ApiResponse::success(Correcao::situacao($prova->fresh()), 'Correção fechada.');
    }

    /** Reabre a correção (só supervisão): professor/corretor voltam a enviar, excluir e alterar. */
    public function reabrirCorrecao(Request $request, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }
        $prova->update(['correcao_fechada_em' => null, 'correcao_fechada_por' => null]);

        return ApiResponse::success(Correcao::situacao($prova->fresh()), 'Correção reaberta.');
    }
}
