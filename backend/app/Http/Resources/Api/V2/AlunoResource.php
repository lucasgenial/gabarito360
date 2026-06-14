<?php

namespace App\Http\Resources\Api\V2;

use App\Enums\MatriculaTurmaStatus;
use App\Enums\StatusEnum;
use App\Models\Aluno;
use App\Models\MatriculaTurma;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin Aluno
 *
 * Espera `matriculasTurmas` e `responsaveis.responsavel` carregados.
 */
class AlunoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection<int, MatriculaTurma> $matriculas */
        $matriculas = $this->relationLoaded('matriculasTurmas')
            ? $this->matriculasTurmas
            : collect();

        $ativa = $matriculas->first(
            fn (MatriculaTurma $m): bool => $m->status === MatriculaTurmaStatus::ACTIVE,
        );

        $responsavel = $this->relationLoaded('responsaveis')
            ? $this->responsaveis->first(fn ($link): bool => $link->fim_em === null && $link->principal)
            : null;

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'matricula' => $this->matricula,
            'data_nascimento' => $this->data_nascimento?->toDateString(),
            'responsavel' => $responsavel?->responsavel?->nome ?? '',
            'turma_id' => $ativa?->turma_id,
            'cpf' => $this->documento,
            'genero' => $this->genero,
            'status' => $this->derivarStatus($matriculas),
            'media_geral' => 0,
            'frequencia' => 0,
        ];
    }

    /**
     * @param  Collection<int, MatriculaTurma>  $matriculas
     */
    private function derivarStatus(Collection $matriculas): string
    {
        if ($this->status === StatusEnum::INACTIVE) {
            return 'inativo';
        }

        $temAtiva = $matriculas->contains(
            fn (MatriculaTurma $m): bool => $m->status === MatriculaTurmaStatus::ACTIVE,
        );

        if (! $temAtiva && $matriculas->contains(
            fn (MatriculaTurma $m): bool => $m->status === MatriculaTurmaStatus::TRANSFERRED,
        )) {
            return 'transferido';
        }

        return 'ativo';
    }
}
