<?php

namespace App\Actions\Aplicacoes;

use App\Enums\AplicacaoStatus;
use App\Enums\GabaritoOficialStatus;
use App\Enums\MatriculaTurmaStatus;
use App\Enums\ProvaStatus;
use App\Models\Aplicacao;
use App\Models\AplicacaoAluno;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAplicacaoAction
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Prova $exam, Turma $class, GabaritoOficial $answerKey, array $attributes, User $actor): Aplicacao
    {
        return DB::transaction(function () use ($exam, $class, $answerKey, $attributes, $actor): Aplicacao {
            $exam = Prova::query()->lockForUpdate()->findOrFail($exam->id);
            $class = Turma::query()->with('escola')->lockForUpdate()->findOrFail($class->id);

            if ($exam->status !== ProvaStatus::PUBLISHED
                || $answerKey->prova_id !== $exam->id
                || $answerKey->status !== GabaritoOficialStatus::CURRENT
                || ! $exam->provaTurmas()->where('turma_id', $class->id)->exists()) {
                throw ValidationException::withMessages([
                    'prova_id' => ['A prova publicada, o gabarito vigente e a turma vinculada devem ser compativeis.'],
                ]);
            }

            $application = Aplicacao::query()->create([
                'prova_id' => $exam->id,
                'turma_id' => $class->id,
                'escola_id' => $class->escola_id,
                'gabarito_oficial_id' => $answerKey->id,
                'titulo' => $attributes['titulo'],
                'inicio_previsto_at' => $attributes['inicio_previsto_at'] ?? null,
                'fim_previsto_at' => $attributes['fim_previsto_at'] ?? null,
                'status' => AplicacaoStatus::SCHEDULED->value,
                'criada_por_id' => $actor->id,
            ]);

            $class->matriculas()
                ->where('status', MatriculaTurmaStatus::ACTIVE->value)
                ->orderBy('id')
                ->each(fn ($enrollment) => AplicacaoAluno::query()->create([
                    'aplicacao_id' => $application->id,
                    'aluno_id' => $enrollment->aluno_id,
                    'matricula_turma_id' => $enrollment->id,
                    'status' => 'previsto',
                ]));

            $this->audit->record(
                AuditAction::APPLICATION_CREATED,
                'aplicacao',
                $application->id,
                $actor->id,
                after: $application->only(['prova_id', 'turma_id', 'gabarito_oficial_id', 'status']),
                metadata: ['alunos_snapshot' => $application->alunos()->count()],
                nucleoId: $class->escola->nucleo_id,
                escolaId: $class->escola_id,
            );

            return $application->refresh();
        });
    }
}
