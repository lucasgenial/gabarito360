<?php

namespace App\Services\Provas;

use App\Enums\GabaritoOficialStatus;
use App\Models\Prova;
use Barryvdh\DomPDF\Facade\Pdf;

class GabaritoPdfService
{
    /**
     * Renderiza o gabarito oficial (vigente, ou o rascunho mais recente) como
     * PDF. Espera `gabaritosOficiais.respostas.questao` carregado.
     */
    public function render(Prova $prova): string
    {
        $gabarito = $prova->gabaritosOficiais
            ->first(fn ($g): bool => $g->status === GabaritoOficialStatus::CURRENT)
            ?? $prova->gabaritosOficiais->sortByDesc('versao')->first();

        $respostas = $gabarito !== null
            ? $gabarito->respostas->sortBy(fn ($r): int => $r->questao?->numero ?? 0)
            : collect();

        return Pdf::loadView('pdf.gabarito', [
            'prova' => $prova,
            'gabarito' => $gabarito,
            'respostas' => $respostas,
            'geradoEm' => now(),
        ])->output();
    }
}
