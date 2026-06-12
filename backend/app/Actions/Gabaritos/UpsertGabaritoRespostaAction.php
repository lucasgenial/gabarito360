<?php

namespace App\Actions\Gabaritos;

use App\Enums\GabaritoOficialStatus;
use App\Enums\ProvaStatus;
use App\Models\GabaritoOficial;
use App\Models\GabaritoResposta;
use App\Models\Prova;
use App\Models\Questao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertGabaritoRespostaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(
        Prova $exam,
        GabaritoOficial $answerKey,
        Questao $question,
        array $attributes,
        User $actor,
    ): GabaritoResposta {
        return DB::transaction(function () use ($exam, $answerKey, $question, $attributes, $actor): GabaritoResposta {
            $lockedAnswerKey = GabaritoOficial::query()
                ->with('prova')
                ->lockForUpdate()
                ->findOrFail($answerKey->id);

            if (
                $lockedAnswerKey->status !== GabaritoOficialStatus::DRAFT
                || $lockedAnswerKey->prova->status !== ProvaStatus::DRAFT
            ) {
                throw ValidationException::withMessages([
                    'gabarito' => ['Respostas somente podem ser alteradas em gabarito e prova rascunho.'],
                ]);
            }

            if (
                $lockedAnswerKey->prova_id !== $exam->id
                || $question->prova_id !== $exam->id
            ) {
                throw ValidationException::withMessages([
                    'questao_id' => ['A questao e o gabarito devem pertencer a prova informada.'],
                ]);
            }

            $alternative = $attributes['alternativa_correta'] ?? null;
            $annulled = (bool) ($attributes['anulada'] ?? false);

            if (
                (! $annulled && (! is_string($alternative) || ! in_array($alternative, $exam->alternativas, true)))
                || ($annulled && $alternative !== null)
            ) {
                throw ValidationException::withMessages([
                    'alternativa_correta' => ['A alternativa deve pertencer a prova ou ser nula quando a questao estiver anulada.'],
                ]);
            }

            $response = GabaritoResposta::query()->updateOrCreate(
                [
                    'gabarito_oficial_id' => $lockedAnswerKey->id,
                    'questao_id' => $question->id,
                ],
                [
                    'prova_id' => $exam->id,
                    'alternativa_correta' => $alternative,
                    'anulada' => $annulled,
                    'peso' => $attributes['peso'] ?? $question->peso_padrao,
                ],
            );

            $this->audit->record(
                action: $response->wasRecentlyCreated
                    ? AuditAction::ANSWER_KEY_RESPONSE_CREATED
                    : AuditAction::ANSWER_KEY_RESPONSE_UPDATED,
                entityType: 'gabarito_resposta',
                entityId: $response->id,
                actorUserId: $actor->id,
                after: $response->only([
                    'prova_id',
                    'gabarito_oficial_id',
                    'questao_id',
                    'alternativa_correta',
                    'anulada',
                    'peso',
                ]),
                metadata: ['gabarito_oficial_id' => $lockedAnswerKey->id],
                nucleoId: $exam->ownerNucleoId(),
                escolaId: $exam->escola_id,
            );

            return $response->refresh();
        });
    }
}
