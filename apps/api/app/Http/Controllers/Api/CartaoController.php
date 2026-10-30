<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Jobs\ProcessarCartaoJob;
use App\Models\Cartao;
use App\Models\Prova;
use App\Services\Omr\AtualizadorStatusProva;
use App\Services\Omr\CalculadoraNota;
use App\Services\Omr\GeradorCartoes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CartaoController extends Controller
{
    private function escopoEscolaIds(Request $request): ?array
    {
        $usuario = $request->user();

        return match ($usuario->perfil) {
            'admin_rede' => null,
            'dir_nucleo' => DB::table('escolas')
                ->join('nucleos', 'escolas.nucleo_id', '=', 'nucleos.id')
                ->join('usuario_escopos', function ($j) use ($usuario) {
                    $j->on('nucleos.id', '=', 'usuario_escopos.escopo_id')
                      ->where('usuario_escopos.usuario_id', $usuario->id)
                      ->where('usuario_escopos.escopo_tipo', 'nucleo');
                })
                ->pluck('escolas.id')->all(),
            'dir_escolar', 'coordenador', 'professor' => DB::table('usuario_escopos')
                ->where('usuario_id', $usuario->id)
                ->where('escopo_tipo', 'escola')
                ->pluck('escopo_id')->all(),
            default => [],
        };
    }

    private function encontrarProva(Request $request, int $provaId): ?Prova
    {
        $escolaIds = $this->escopoEscolaIds($request);
        $query     = Prova::query();

        if ($escolaIds !== null) {
            $query->whereIn('escola_id', $escolaIds);
        }

        return $query->find($provaId);
    }

    public function index(Request $request, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $cartoes = Cartao::with('aluno:id,nome,matricula')
            ->where('prova_id', $provaId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Cartao $c) => array_merge($c->toArray(), [
                'aluno_nome' => $c->aluno?->nome,
            ]));

        return ApiResponse::success(['cartoes' => $cartoes]);
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
        ]);
    }

    public function store(Request $request, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
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

        // Com fila síncrona o cartão já foi lido: devolve o resultado para o celular.
        $cartao->refresh()->load('aluno:id,nome');
        $nota = DB::table('notas')->where('cartao_id', $cartao->id)->first();

        return ApiResponse::success([
            'id'         => $cartao->id,
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
        if (!$cartao || !$this->encontrarProva($request, $cartao->prova_id)) {
            return ApiResponse::notFound('Cartão não encontrado.');
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
        if (!$cartao || !$this->encontrarProva($request, $cartao->prova_id)) {
            return ApiResponse::notFound('Cartão não encontrado.');
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
        if (!$cartao || !$this->encontrarProva($request, $cartao->prova_id)) {
            return ApiResponse::notFound('Cartão não encontrado.');
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
}
