<?php

namespace App\Http\Controllers\Api\V2\Leituras;

use App\Actions\Leituras\ConfirmReadingAction;
use App\Actions\Leituras\CreatePreliminaryReadingAction;
use App\Actions\Leituras\ReviewReadingAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Leituras\CaptureLeituraRequest;
use App\Http\Requests\Api\V2\Leituras\ConfirmLeituraRequest;
use App\Http\Requests\Api\V2\Leituras\ReviewLeituraRequest;
use App\Http\Resources\Api\V2\LeituraResource;
use App\Models\Aplicacao;
use App\Models\Arquivo;
use App\Models\LeituraCartao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeituraController extends BaseApiController
{
    public function index(Request $request, Aplicacao $aplicacao): JsonResponse
    {
        Gate::authorize('view', $aplicacao);

        $leituras = $aplicacao->leituras()
            ->with(['aplicacaoAluno.aluno', 'respostasDetectadas.questao'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->paginatedResponse($leituras, LeituraResource::class);
    }

    public function capturar(CaptureLeituraRequest $request, Aplicacao $aplicacao, CreatePreliminaryReadingAction $action): JsonResponse
    {
        $actor = $this->actor($request);
        $exam = $aplicacao->prova()->firstOrFail();

        if ($exam->modelo_cartao_id === null) {
            throw ValidationException::withMessages([
                'prova' => ['A prova nao possui modelo de cartao para leitura OMR.'],
            ]);
        }

        $questionsByNumber = $exam->questoes()->pluck('id', 'numero');
        $existing = LeituraCartao::query()->where('operacao_id', (string) $request->input('operacao_id'))->first();

        // Replay idempotente: reusa o arquivo original para reproduzir o hash da
        // operação — a action devolve a leitura existente ou 409 se o conteúdo
        // divergir, sem armazenar um arquivo órfão.
        if ($existing !== null) {
            $reading = $action->execute(
                $aplicacao,
                $this->readingAttributes($request, $questionsByNumber, $existing->arquivo_original_id),
                $actor,
            );

            return $this->successResponse(LeituraResource::make($this->load($reading)), 200);
        }

        $file = $request->file('imagem');
        $disk = (string) config('filesystems.private');

        $reading = DB::transaction(function () use ($request, $aplicacao, $action, $actor, $questionsByNumber, $file, $disk): LeituraCartao {
            $path = Storage::disk($disk)->putFile('leituras', $file);
            $arquivo = Arquivo::query()->create([
                'disco' => $disk,
                'caminho' => $path,
                'nome_original' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'tamanho_bytes' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'classificacao' => 'interno',
                'proprietario_tipo' => 'leitura_cartao',
                'proprietario_id' => $aplicacao->id,
                'criado_por_id' => $actor->id,
            ]);

            return $action->execute(
                $aplicacao,
                $this->readingAttributes($request, $questionsByNumber, $arquivo->id),
                $actor,
            );
        });

        return $this->successResponse(LeituraResource::make($this->load($reading)), 201);
    }

    /**
     * Monta os atributos da action de leitura, resolvendo questão por número.
     *
     * @param  Collection<int, string>  $questionsByNumber
     * @return array<string, mixed>
     */
    private function readingAttributes(CaptureLeituraRequest $request, Collection $questionsByNumber, string $arquivoId): array
    {
        $respostas = collect($request->input('respostas'))
            ->map(fn (array $resposta): array => [
                'questao_id' => $questionsByNumber[(int) $resposta['numero']],
                'alternativa_detectada' => $resposta['alternativa_detectada'] ?? null,
                'tipo_deteccao' => $resposta['tipo_deteccao'],
                'confianca' => $resposta['confianca'] ?? null,
            ])->all();

        return [
            'operacao_id' => $request->input('operacao_id'),
            'aplicacao_aluno_id' => $request->input('aplicacao_aluno_id'),
            'arquivo_original_id' => $arquivoId,
            'dispositivo_id' => $request->input('dispositivo_id'),
            'codigo_impresso_detectado' => $request->input('codigo_impresso_detectado'),
            'codigo_sistema_proposto' => $request->input('codigo_sistema_proposto'),
            'confianca_geral' => $request->input('confianca_geral'),
            'requer_revisao' => $request->boolean('requer_revisao'),
            'omr_versao' => $request->input('omr_versao'),
            'omr_metadados' => $request->input('omr_metadados'),
            'alertas' => $request->input('alertas'),
            'respostas' => $respostas,
        ];
    }

    public function confirmar(ConfirmLeituraRequest $request, LeituraCartao $leitura, ConfirmReadingAction $action): JsonResponse
    {
        $key = $request->header('Idempotency-Key') ?: (string) Str::uuid();
        $outcome = $action->execute($leitura, $key, $this->actor($request));

        return $this->successResponse(
            LeituraResource::make($this->load($outcome['reading'])),
            200,
            ['resultado_id' => $outcome['result']->id],
        );
    }

    public function revisar(ReviewLeituraRequest $request, LeituraCartao $leitura, ReviewReadingAction $action): JsonResponse
    {
        $leitura->loadMissing('aplicacao.prova');
        $numero = (int) $request->input('questao');
        $questao = $leitura->aplicacao->prova->questoes()->where('numero', $numero)->first();

        if ($questao === null) {
            throw ValidationException::withMessages(['questao' => ['Questao inexistente na prova.']]);
        }

        $decisao = (string) $request->input('decisao');
        $branco = strtolower($decisao) === 'branco';

        $action->execute($leitura, [
            'motivo' => $request->input('motivo') ?: 'Revisao manual da pendencia.',
            'respostas' => [[
                'questao_id' => $questao->id,
                'alternativa_final' => $branco ? null : strtoupper($decisao),
                'tipo_deteccao' => $branco ? 'branco' : 'marcada',
            ]],
        ], $this->actor($request));

        return $this->successResponse(LeituraResource::make($this->load($leitura->refresh())));
    }

    private function load(LeituraCartao $leitura): LeituraCartao
    {
        return $leitura->load(['aplicacaoAluno.aluno', 'respostasDetectadas.questao']);
    }
}
