<?php

namespace App\Actions\Leituras;

use App\Enums\AplicacaoStatus;
use App\Enums\LeituraCartaoStatus;
use App\Events\ApplicationProgressUpdated;
use App\Events\ReadingReviewRequired;
use App\Models\Aplicacao;
use App\Models\AplicacaoAluno;
use App\Models\Arquivo;
use App\Models\LeituraCartao;
use App\Models\LogSincronizacao;
use App\Models\RespostaDetectada;
use App\Models\User;
use App\Services\Applications\ApplicationMetrics;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Cards\CardCodeGenerator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CreatePreliminaryReadingAction
{
    public function __construct(
        private CardCodeGenerator $codes,
        private AuditService $audit,
        private ApplicationMetrics $metrics,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Aplicacao $application, array $attributes, User $actor): LeituraCartao
    {
        $payloadHash = hash('sha256', json_encode($attributes, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($application, $attributes, $actor, $payloadHash): LeituraCartao {
            $existingLog = LogSincronizacao::query()
                ->where('operacao_id', $attributes['operacao_id'])
                ->lockForUpdate()
                ->first();

            if ($existingLog) {
                if (! hash_equals($existingLog->payload_hash, $payloadHash)) {
                    throw new ConflictHttpException('A operacao ja foi usada com outro conteudo.');
                }

                return LeituraCartao::query()->where('operacao_id', $attributes['operacao_id'])->firstOrFail();
            }

            $application = Aplicacao::query()->with(['prova', 'escola'])->lockForUpdate()->findOrFail($application->id);

            if ($application->status !== AplicacaoStatus::IN_PROGRESS->value) {
                throw new ConflictHttpException('A aplicacao precisa estar em andamento para receber leituras.');
            }

            $student = AplicacaoAluno::query()
                ->where('aplicacao_id', $application->id)
                ->lockForUpdate()
                ->findOrFail($attributes['aplicacao_aluno_id']);
            Arquivo::query()->findOrFail($attributes['arquivo_original_id']);

            $answers = collect($attributes['respostas']);
            $requiresReview = (bool) ($attributes['requer_revisao'] ?? false)
                || $answers->contains(fn (array $answer): bool => in_array($answer['tipo_deteccao'], ['dupla', 'ambigua'], true));
            $printed = $attributes['codigo_impresso_detectado'] ?? null;

            $reading = LeituraCartao::query()->create([
                'aplicacao_id' => $application->id,
                'aplicacao_aluno_id' => $student->id,
                'modelo_cartao_id' => $application->prova->modelo_cartao_id,
                'arquivo_original_id' => $attributes['arquivo_original_id'],
                'capturada_por_id' => $actor->id,
                'dispositivo_id' => $attributes['dispositivo_id'] ?? null,
                'operacao_id' => $attributes['operacao_id'],
                'codigo_impresso_detectado' => $printed,
                'codigo_impresso_normalizado' => $this->codes->normalizePrinted($printed),
                'codigo_sistema_proposto' => $attributes['codigo_sistema_proposto'] ?? null,
                'status' => $requiresReview ? LeituraCartaoStatus::UNDER_REVIEW->value : LeituraCartaoStatus::RECEIVED->value,
                'omr_versao' => $attributes['omr_versao'] ?? null,
                'omr_configuracao_checksum' => $attributes['omr_configuracao_checksum'] ?? null,
                'omr_metadados' => $attributes['omr_metadados'] ?? null,
                'confianca_geral' => $attributes['confianca_geral'] ?? null,
                'requer_revisao' => $requiresReview,
                'alertas' => $attributes['alertas'] ?? null,
            ]);

            foreach ($answers as $answer) {
                RespostaDetectada::query()->create([
                    'leitura_cartao_id' => $reading->id,
                    'questao_id' => $answer['questao_id'],
                    'alternativa_detectada' => $answer['alternativa_detectada'] ?? null,
                    'alternativa_final' => $answer['alternativa_detectada'] ?? null,
                    'tipo_deteccao' => $answer['tipo_deteccao'],
                    'confianca' => $answer['confianca'] ?? null,
                ]);
            }

            LogSincronizacao::query()->create([
                'operacao_id' => $attributes['operacao_id'],
                'usuario_id' => $actor->id,
                'dispositivo_id' => $attributes['dispositivo_id'] ?? null,
                'aplicacao_id' => $application->id,
                'tipo' => 'leitura.receber',
                'status' => 'processado',
                'tentativas' => 1,
                'payload_hash' => $payloadHash,
                'processado_at' => now(),
            ]);

            $this->audit->record(
                AuditAction::READING_RECEIVED,
                'leitura_cartao',
                $reading->id,
                $actor->id,
                after: $reading->only(['aplicacao_id', 'aplicacao_aluno_id', 'status', 'requer_revisao', 'omr_versao']),
                metadata: ['operacao_id' => $reading->operacao_id, 'respostas' => $answers->count()],
                nucleoId: $application->escola->nucleo_id,
                escolaId: $application->escola_id,
            );
            ApplicationProgressUpdated::dispatch($application, $this->metrics->for($application));

            if ($requiresReview) {
                ReadingReviewRequired::dispatch($reading);
            }

            return $reading->load('respostasDetectadas');
        });
    }
}
