<?php

namespace App\Actions\Alunos;

use App\Enums\StatusEnum;
use App\Models\Aluno;
use App\Models\Responsavel;
use Illuminate\Support\Facades\DB;

class SyncResponsavelAction
{
    /**
     * Garante que o aluno tenha o responsável principal informado (por nome),
     * encerrando o vínculo principal anterior quando for outro responsável.
     */
    public function execute(Aluno $student, string $nomeResponsavel): void
    {
        $nome = trim($nomeResponsavel);

        if ($nome === '') {
            return;
        }

        DB::transaction(function () use ($student, $nome): void {
            $responsavel = Responsavel::query()->firstOrCreate(
                ['nome' => $nome],
                ['status' => StatusEnum::ACTIVE->value],
            );

            $vigente = $student->responsaveis()
                ->whereNull('fim_em')
                ->where('principal', true)
                ->first();

            if ($vigente !== null && $vigente->responsavel_id === $responsavel->id) {
                return;
            }

            if ($vigente !== null) {
                $vigente->update(['fim_em' => today(), 'principal' => false]);
            }

            $student->responsaveis()->create([
                'responsavel_id' => $responsavel->id,
                'parentesco' => 'responsavel',
                'principal' => true,
                'autorizado_contato' => true,
                'inicio_em' => today(),
                'fim_em' => null,
            ]);
        });
    }
}
