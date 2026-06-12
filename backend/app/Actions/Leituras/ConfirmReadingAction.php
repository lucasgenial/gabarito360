<?php

namespace App\Actions\Leituras;

use App\Actions\Resultados\CalculateResultAction;
use App\Enums\LeituraCartaoStatus;
use App\Events\ApplicationProgressUpdated;
use App\Models\CartaoResposta;
use App\Models\LeituraCartao;
use App\Models\LogSincronizacao;
use App\Models\Resultado;
use App\Models\User;
use App\Services\Applications\ApplicationMetrics;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Cards\CardCodeGenerator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ConfirmReadingAction
{
    public function __construct(
        private CalculateResultAction $calculate,
        private CardCodeGenerator $codes,
        private AuditService $audit,
        private ApplicationMetrics $metrics,
    ) {}

    /** @return array{reading: LeituraCartao, result: Resultado} */
    public function execute(LeituraCartao $reading, string $idempotencyKey, User $actor): array
    {
        $operationId = 'confirm:'.$idempotencyKey;
        $payloadHash = hash('sha256', $reading->id);

        return DB::transaction(function () use ($reading, $operationId, $payloadHash, $actor): array {
            $existingLog = LogSincronizacao::query()->where('operacao_id', $operationId)->lockForUpdate()->first();

            if ($existingLog) {
                if (! hash_equals($existingLog->payload_hash, $payloadHash)) {
                    throw new ConflictHttpException('A chave de idempotencia ja foi usada em outra leitura.');
                }

                $confirmed = LeituraCartao::query()->findOrFail($reading->id);

                return [
                    'reading' => $confirmed,
                    'result' => $confirmed->resultados()->where('status', 'vigente')->firstOrFail(),
                ];
            }

            $reading = LeituraCartao::query()
                ->with(['aplicacao.escola', 'aplicacaoAluno'])
                ->lockForUpdate()
                ->findOrFail($reading->id);

            if ($reading->requer_revisao) {
                throw new ConflictHttpException('A leitura requer revisao antes da confirmacao.');
            }

            if ($reading->confirmada_at !== null || $reading->cancelada_at !== null) {
                throw new ConflictHttpException('A leitura nao esta disponivel para confirmacao.');
            }

            $card = $this->resolveCard($reading);
            $reading->update([
                'cartao_resposta_id' => $card->id,
                'status' => LeituraCartaoStatus::CONFIRMED->value,
                'confirmada_at' => now(),
            ]);
            $result = $this->calculate->execute($reading, $actor);

            LogSincronizacao::query()->create([
                'operacao_id' => $operationId,
                'usuario_id' => $actor->id,
                'dispositivo_id' => $reading->dispositivo_id,
                'aplicacao_id' => $reading->aplicacao_id,
                'tipo' => 'leitura.confirmar',
                'status' => 'processado',
                'tentativas' => 1,
                'payload_hash' => $payloadHash,
                'processado_at' => now(),
            ]);

            $this->audit->record(
                AuditAction::READING_CONFIRMED,
                'leitura_cartao',
                $reading->id,
                $actor->id,
                after: $reading->only(['status', 'cartao_resposta_id', 'confirmada_at']),
                metadata: ['resultado_id' => $result->id, 'operacao_id' => $operationId],
                nucleoId: $reading->aplicacao->escola->nucleo_id,
                escolaId: $reading->aplicacao->escola_id,
            );
            ApplicationProgressUpdated::dispatch($reading->aplicacao, $this->metrics->for($reading->aplicacao));

            return ['reading' => $reading->refresh(), 'result' => $result];
        });
    }

    private function resolveCard(LeituraCartao $reading): CartaoResposta
    {
        $studentId = $reading->aplicacaoAluno->aluno_id;
        $printed = $reading->codigo_impresso_normalizado;

        if ($printed !== null) {
            $foreign = CartaoResposta::query()
                ->where('prova_id', $reading->aplicacao->prova_id)
                ->where('codigo_impresso_normalizado', $printed)
                ->lockForUpdate()
                ->first();

            if ($foreign && $foreign->aluno_id !== $studentId) {
                throw new ConflictHttpException('O codigo impresso ja esta associado a outro aluno nesta prova.');
            }

            if ($foreign) {
                return $foreign;
            }
        }

        $existing = CartaoResposta::query()
            ->where('prova_id', $reading->aplicacao->prova_id)
            ->where('aluno_id', $studentId)
            ->where('status', 'vigente')
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        $systemCode = $reading->codigo_sistema_proposto ?: $this->uniqueSystemCode();

        return CartaoResposta::query()->create([
            'prova_id' => $reading->aplicacao->prova_id,
            'aluno_id' => $studentId,
            'aplicacao_id' => $reading->aplicacao_id,
            'codigo_impresso' => $reading->codigo_impresso_detectado,
            'codigo_impresso_normalizado' => $printed,
            'codigo_sistema' => $systemCode,
            'codigo_sistema_afixado' => $reading->codigo_sistema_proposto !== null,
            'motivo_sem_codigo_impresso' => $printed === null ? 'nao_detectado_na_captura' : null,
            'status' => 'vigente',
        ]);
    }

    private function uniqueSystemCode(): string
    {
        do {
            $code = $this->codes->generate();
        } while (CartaoResposta::query()->where('codigo_sistema', $code)->exists());

        return $code;
    }
}
