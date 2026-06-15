<?php

namespace App\Actions\Relatorios;

use App\Models\Prova;
use App\Models\SnapshotIndicador;
use App\Models\User;
use App\Services\Relatorios\ProvaReportService;
use Illuminate\Support\Collection;

/**
 * Persiste uma fotografia (snapshot) dos indicadores de uma prova, alimentando
 * dashboards e comparações históricas sem recomputar tudo a cada carga.
 */
class GenerateIndicatorSnapshotAction
{
    public function __construct(private ProvaReportService $reports) {}

    /**
     * @param  Collection<int, string>  $applicationIds
     */
    public function execute(Prova $prova, Collection $applicationIds, User $actor): SnapshotIndicador
    {
        $report = $this->reports->build($prova, $applicationIds);
        $kpis = $report['kpis'];

        return SnapshotIndicador::query()->create([
            'escopo_tipo' => 'prova',
            'escopo_id' => $prova->id,
            'nucleo_id' => $prova->ownerNucleoId(),
            'prova_id' => $prova->id,
            'total_resultados' => $kpis['cartoes_corrigidos'],
            'media_nota' => $kpis['media_nota'],
            'indicadores' => [
                'kpis' => $kpis,
                'acertos_por_tema' => $report['acertos_por_tema'],
            ],
            'gerado_por_id' => $actor->id,
            'gerado_at' => now(),
        ]);
    }
}
