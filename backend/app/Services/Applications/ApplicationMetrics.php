<?php

namespace App\Services\Applications;

use App\Models\Aplicacao;

class ApplicationMetrics
{
    /** @return array<string, int|string> */
    public function for(Aplicacao $application): array
    {
        return [
            'status' => $application->status,
            'expected_students' => $application->alunos()->count(),
            'readings' => $application->leituras()->count(),
            'pending_review' => $application->leituras()->where('requer_revisao', true)->count(),
            'confirmed_readings' => $application->leituras()->where('status', 'confirmada')->count(),
            'current_results' => $application->resultados()->where('status', 'vigente')->count(),
        ];
    }
}
