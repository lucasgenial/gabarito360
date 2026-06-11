<?php

namespace App\Actions\Provas;

use App\Enums\GabaritoOficialStatus;
use App\Enums\ProvaStatus;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Gabaritos\GabaritoCompletenessValidator;
use App\Services\Provas\ProvaConfigurationValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PublishProva
{
    public function __construct(
        private GabaritoCompletenessValidator $completenessValidator,
        private ProvaConfigurationValidator $configurationValidator,
        private AuditService $audit,
    ) {}

    /** @return array{prova: Prova, gabarito: GabaritoOficial} */
    public function execute(Prova $exam, GabaritoOficial $answerKey, User $actor): array
    {
        return DB::transaction(function () use ($exam, $answerKey, $actor): array {
            $lockedExam = Prova::query()->lockForUpdate()->findOrFail($exam->id);

            if ($lockedExam->status !== ProvaStatus::DRAFT) {
                throw new ConflictHttpException('A prova nao esta mais disponivel para publicacao.');
            }

            $lockedAnswerKey = GabaritoOficial::query()
                ->where('prova_id', $lockedExam->id)
                ->lockForUpdate()
                ->findOrFail($answerKey->id);

            if ($lockedAnswerKey->status !== GabaritoOficialStatus::DRAFT) {
                throw new ConflictHttpException('O gabarito selecionado nao esta mais em rascunho.');
            }

            if ($lockedExam->gabaritosOficiais()->where('status', GabaritoOficialStatus::CURRENT->value)->exists()) {
                throw new ConflictHttpException('A prova ja possui um gabarito oficial vigente.');
            }

            $this->configurationValidator->assertValid($this->configuration($lockedExam), $lockedExam);
            $validation = $this->completenessValidator->validate($lockedAnswerKey);

            if (! $validation['completo']) {
                throw ValidationException::withMessages([
                    'gabarito_oficial_id' => ['O gabarito oficial selecionado esta incompleto.'],
                    'problemas' => $validation['problemas'],
                ]);
            }

            $publishedAt = now();
            $examBefore = $lockedExam->only(['status', 'publicada_at']);
            $answerKeyBefore = $lockedAnswerKey->only(['status', 'publicado_por', 'publicado_at']);

            // O gatilho exige que o gabarito se torne vigente enquanto a prova ainda esta em rascunho.
            $lockedAnswerKey->update([
                'status' => GabaritoOficialStatus::CURRENT,
                'publicado_por' => $actor->id,
                'publicado_at' => $publishedAt,
            ]);
            $lockedExam->update([
                'status' => ProvaStatus::PUBLISHED,
                'publicada_at' => $publishedAt,
            ]);

            $this->audit->record(
                action: AuditAction::ANSWER_KEY_PUBLISHED,
                entityType: 'gabarito_oficial',
                entityId: $lockedAnswerKey->id,
                actorUserId: $actor->id,
                before: $answerKeyBefore,
                after: $lockedAnswerKey->only(['status', 'publicado_por', 'publicado_at']),
                metadata: ['prova_id' => $lockedExam->id],
                nucleoId: $lockedExam->ownerNucleoId(),
                escolaId: $lockedExam->escola_id,
            );
            $this->audit->record(
                action: AuditAction::EXAM_PUBLISHED,
                entityType: 'prova',
                entityId: $lockedExam->id,
                actorUserId: $actor->id,
                before: $examBefore,
                after: $lockedExam->only(['status', 'publicada_at']),
                metadata: ['gabarito_oficial_id' => $lockedAnswerKey->id],
                nucleoId: $lockedExam->ownerNucleoId(),
                escolaId: $lockedExam->escola_id,
            );

            return [
                'prova' => $lockedExam->refresh(),
                'gabarito' => $lockedAnswerKey->refresh(),
            ];
        });
    }

    /** @return array<string, mixed> */
    private function configuration(Prova $exam): array
    {
        return $exam->only([
            'nucleo_id',
            'escola_id',
            'modelo_cartao_id',
            'codigo',
            'titulo',
            'descricao',
            'tipo',
            'nivel',
            'ano_referencia',
            'quantidade_questoes',
            'quantidade_alternativas',
            'alternativas',
        ]);
    }
}
