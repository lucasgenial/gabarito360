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

class FinishAplicacaoAction
{
    public function __construct(private AuditService $audit, private ApplicationMetrics $metrics) {}

    public function execute(Aplicacao $application, User $actor): Aplicacao
    {
        return DB::transaction(function () use ($application, $actor): Aplicacao {
            $application = Aplicacao::query()->with('escola')->lockForUpdate()->findOrFail($application->id);

            if ($application->status !== AplicacaoStatus::IN_PROGRESS->value) {
                throw new ConflictHttpException('A aplicacao nao esta em andamento.');
            }

            if ($application->leituras()->where('requer_revisao', true)->exists()) {
                throw new ConflictHttpException('Existem leituras que ainda requerem revisao.');
            }

            $application->update(['status' => AplicacaoStatus::FINISHED->value, 'finalizada_at' => now()]);
            $this->audit->record(
                AuditAction::APPLICATION_FINISHED,
                'aplicacao',
                $application->id,
                $actor->id,
                after: $application->only(['status', 'finalizada_at']),
                nucleoId: $application->escola->nucleo_id,
                escolaId: $application->escola_id,
            );
            ApplicationProgressUpdated::dispatch($application, $this->metrics->for($application));

            return $application->refresh();
        });
    }
}
