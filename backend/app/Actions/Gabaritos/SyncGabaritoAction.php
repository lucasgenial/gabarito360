<?php

namespace App\Actions\Gabaritos;

use App\Enums\GabaritoOficialStatus;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SyncGabaritoAction
{
    public function __construct(
        private CreateGabaritoOficialAction $createGabarito,
        private UpsertGabaritoRespostaAction $upsertResposta,
    ) {}

    /**
     * Aplica as respostas oficiais (por número de questão) ao gabarito rascunho
     * da prova, criando o gabarito DRAFT quando necessário.
     *
     * @param  array<int, array{questao: int, correta: ?string}>  $respostas
     */
    public function execute(Prova $prova, array $respostas, User $actor): GabaritoOficial
    {
        return DB::transaction(function () use ($prova, $respostas, $actor): GabaritoOficial {
            $gabarito = $prova->gabaritosOficiais()
                ->where('status', GabaritoOficialStatus::DRAFT->value)
                ->first()
                ?? $this->createGabarito->execute($prova, $actor);

            foreach ($respostas as $resposta) {
                $numero = (int) $resposta['questao'];
                $questao = $prova->questoes()->where('numero', $numero)->first();

                if ($questao === null) {
                    throw ValidationException::withMessages([
                        'respostas' => ["A questao {$numero} nao existe na prova."],
                    ]);
                }

                $correta = $resposta['correta'] ?? null;
                $anulada = $correta === null || $correta === '';

                $this->upsertResposta->execute($prova, $gabarito, $questao, [
                    'alternativa_correta' => $anulada ? null : Str::upper((string) $correta),
                    'anulada' => $anulada,
                ], $actor);
            }

            return $gabarito->refresh();
        });
    }
}
