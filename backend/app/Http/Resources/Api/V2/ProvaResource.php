<?php

namespace App\Http\Resources\Api\V2;

use App\Enums\GabaritoOficialStatus;
use App\Enums\ProvaStatus;
use App\Models\Prova;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Prova
 *
 * Espera `disciplina`, `serieAno`, `provaTurmas.turma` e `gabaritosOficiais`
 * carregados.
 */
class ProvaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $turmas = $this->relationLoaded('provaTurmas') ? $this->provaTurmas : collect();
        $vigente = $this->relationLoaded('gabaritosOficiais')
            ? $this->gabaritosOficiais->first(fn ($g): bool => $g->status === GabaritoOficialStatus::CURRENT)
            : null;
        $datas = $turmas->pluck('data_prevista')->filter();

        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'disciplina' => $this->disciplina?->nome,
            'serie' => $this->serieAno?->nome,
            'num_questoes' => $this->quantidade_questoes,
            'status' => $this->mapStatus($this->status),
            'turmas' => $turmas->map(fn ($pt) => $pt->turma?->nome)->filter()->values()->all(),
            'aplicacao' => $datas->isNotEmpty() ? $datas->min()->toDateString() : null,
            'versao_gabarito' => $vigente?->versao ?? 0,
        ];
    }

    private function mapStatus(ProvaStatus $status): string
    {
        return match ($status) {
            ProvaStatus::DRAFT => 'rascunho',
            ProvaStatus::PUBLISHED => 'publicada',
            ProvaStatus::FINISHED, ProvaStatus::ARCHIVED => 'corrigida',
        };
    }
}
