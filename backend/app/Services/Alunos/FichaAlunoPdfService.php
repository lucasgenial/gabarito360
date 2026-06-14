<?php

namespace App\Services\Alunos;

use App\Enums\MatriculaTurmaStatus;
use App\Models\Aluno;
use Barryvdh\DomPDF\Facade\Pdf;

class FichaAlunoPdfService
{
    /**
     * Renderiza a ficha do aluno como PDF (bytes). Espera `escola`,
     * `matriculasTurmas.turma` e `responsaveis.responsavel` carregados.
     */
    public function render(Aluno $aluno): string
    {
        $matricula = $aluno->matriculasTurmas
            ->first(fn ($m): bool => $m->status === MatriculaTurmaStatus::ACTIVE);

        $responsavel = $aluno->responsaveis
            ->first(fn ($link): bool => $link->fim_em === null && $link->principal)
            ?->responsavel;

        return Pdf::loadView('pdf.ficha-aluno', [
            'aluno' => $aluno,
            'escola' => $aluno->escola,
            'turma' => $matricula?->turma,
            'matricula' => $matricula,
            'responsavel' => $responsavel,
            'geradoEm' => now(),
        ])->output();
    }
}
