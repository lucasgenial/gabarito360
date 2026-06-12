<?php

namespace App\Actions\Aplicacoes;

use App\Enums\AplicacaoStatus;
use App\Events\ApplicationProgressUpdated;
use App\Models\Aplicacao;
use App\Models\User;
use App\Services\Applications\ApplicationMetrics;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class StartAplicacaoAction
{
    public function __construct(private AuditService $audit, private ApplicationMetrics $metrics) {}

    public function execute(Aplicacao $application, User $actor): Aplicacao
    {
        return DB::transaction(function () use ($application, $actor): Aplicacao {
            $application = Aplicacao::query()->with('escola')->lockForUpdate()->findOrFail($application->id);

            if ($application->status !== AplicacaoStatus::SCHEDULED->value) {
                throw new ConflictHttpException('A aplicacao nao esta agendada.');
            }

            $application->update(['status' => AplicacaoStatus::IN_PROGRESS->value, 'iniciada_at' => now()]);
            $this->audit->record(
                AuditAction::APPLICATION_STARTED,
                'aplicacao',
                $application->id,
                $actor->id,
                after: $application->only(['status', 'iniciada_at']),
                nucleoId: $application->escola->nucleo_id,
                escolaId: $application->escola_id,
            );
            ApplicationProgressUpdated::dispatch($application, $this->metrics->for($application));

            return $application->refresh();
        });
    }
}
