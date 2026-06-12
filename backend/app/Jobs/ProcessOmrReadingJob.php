<?php

namespace App\Jobs;

use App\Models\LeituraCartao;
use App\Models\RespostaDetectada;
use App\Services\Omr\OmrProcessor;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProcessOmrReadingJob implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(public readonly string $readingId) {}

    public function handle(OmrProcessor $processor): void
    {
        $reading = LeituraCartao::query()
            ->with(['aplicacao.prova.questoes', 'arquivoOriginal'])
            ->findOrFail($this->readingId);
        $result = $processor->process(Storage::disk($reading->arquivoOriginal->disco)->path($reading->arquivoOriginal->caminho));
        $questions = $reading->aplicacao->prova->questoes->keyBy('numero');

        foreach ($result['responses'] as $response) {
            $question = $questions->get($response['question']);

            if ($question === null) {
                continue;
            }

            RespostaDetectada::query()->updateOrCreate(
                ['leitura_cartao_id' => $reading->id, 'questao_id' => $question->id],
                [
                    'alternativa_detectada' => $response['alternative'],
                    'alternativa_final' => $response['alternative'],
                    'tipo_deteccao' => $this->type($response['type']),
                    'confianca' => $response['confidence'],
                ],
            );
        }

        $reading->update([
            'status' => $result['review_required'] ? 'em_revisao' : 'recebida',
            'omr_versao' => $result['processor_version'],
            'omr_configuracao_checksum' => $result['model']['config_sha256'],
            'omr_metadados' => ['quality' => $result['quality'], 'geometry' => $result['geometry']],
            'confianca_geral' => $result['confidence'],
            'requer_revisao' => true,
            'alertas' => $result['alerts'],
        ]);
    }

    public function failed(Throwable $exception): void
    {
        LeituraCartao::query()->whereKey($this->readingId)->update([
            'status' => 'falhou',
            'requer_revisao' => true,
            'alertas' => ['OMR_PROCESSING_FAILED'],
        ]);
    }

    private function type(string $type): string
    {
        return match ($type) {
            'marked' => 'marcada',
            'blank' => 'branco',
            'double' => 'dupla',
            'ambiguous' => 'ambigua',
            default => $type,
        };
    }
}
