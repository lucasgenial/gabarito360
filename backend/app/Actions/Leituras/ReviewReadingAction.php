<?php

namespace App\Actions\Leituras;

use App\Enums\LeituraCartaoStatus;
use App\Events\ApplicationProgressUpdated;
use App\Models\LeituraCartao;
use App\Models\User;
use App\Services\Applications\ApplicationMetrics;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReviewReadingAction
{
    public function __construct(private AuditService $audit, private ApplicationMetrics $metrics) {}

    /** @param array<string, mixed> $attributes */
    public function execute(LeituraCartao $reading, array $attributes, User $actor): LeituraCartao
    {
        return DB::transaction(function () use ($reading, $attributes, $actor): LeituraCartao {
            $reading = LeituraCartao::query()->with('aplicacao.escola')->lockForUpdate()->findOrFail($reading->id);

            if ($reading->confirmada_at !== null || $reading->cancelada_at !== null) {
                throw new ConflictHttpException('A leitura nao pode mais ser revisada.');
            }

            foreach ($attributes['respostas'] as $answer) {
                $updated = $reading->respostasDetectadas()->where('questao_id', $answer['questao_id'])->update([
                    'alternativa_final' => $answer['alternativa_final'] ?? null,
                    'tipo_deteccao' => $answer['tipo_deteccao'] ?? 'marcada',
                    'alterada_manualmente' => true,
                    'motivo_alteracao' => $attributes['motivo'],
                    'alterada_por_id' => $actor->id,
                    'alterada_at' => now(),
                ]);

                if ($updated !== 1) {
                    throw ValidationException::withMessages([
                        'respostas' => ['Todas as respostas revisadas devem pertencer a leitura informada.'],
                    ]);
                }
            }

            $reading->update([
                'status' => LeituraCartaoStatus::REVIEWED->value,
                'requer_revisao' => false,
                'revisada_por_id' => $actor->id,
                'revisada_at' => now(),
                'motivo_revisao' => $attributes['motivo'],
            ]);

            $this->audit->record(
                AuditAction::READING_REVIEWED,
                'leitura_cartao',
                $reading->id,
                $actor->id,
                after: $reading->only(['status', 'requer_revisao', 'revisada_por_id', 'revisada_at']),
                metadata: ['respostas_alteradas' => count($attributes['respostas']), 'motivo' => $attributes['motivo']],
                nucleoId: $reading->aplicacao->escola->nucleo_id,
                escolaId: $reading->aplicacao->escola_id,
            );
            ApplicationProgressUpdated::dispatch($reading->aplicacao, $this->metrics->for($reading->aplicacao));

            return $reading->load('respostasDetectadas');
        });
    }
}
