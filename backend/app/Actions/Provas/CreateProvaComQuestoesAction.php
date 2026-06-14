<?php

namespace App\Actions\Provas;

use App\Enums\QuestaoStatus;
use App\Models\Prova;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateProvaComQuestoesAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /**
     * Cria a prova (rascunho) e as N questões internas (numero 1..N) em uma
     * transação. O modelo de cartão OMR é opcional no B4.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $actor): Prova
    {
        return DB::transaction(function () use ($attributes, $actor): Prova {
            $prova = Prova::query()->create([
                ...$attributes,
                'criado_por' => $actor->id,
            ])->refresh();

            $total = (int) $prova->quantidade_questoes;
            $peso = $this->peso($prova, $total);

            $questoes = [];
            for ($numero = 1; $numero <= $total; $numero++) {
                $questoes[] = [
                    'numero' => $numero,
                    'status' => QuestaoStatus::ACTIVE->value,
                    'peso_padrao' => $peso,
                ];
            }

            $prova->questoes()->createMany($questoes);

            $this->audit->record(
                action: AuditAction::EXAM_CREATED,
                entityType: 'prova',
                entityId: $prova->id,
                actorUserId: $actor->id,
                after: $prova->only(['codigo', 'titulo', 'disciplina_id', 'quantidade_questoes', 'quantidade_alternativas', 'status']),
                nucleoId: $prova->ownerNucleoId(),
                escolaId: $prova->escola_id,
            );

            return $prova;
        });
    }

    private function peso(Prova $prova, int $total): float
    {
        $padrao = $prova->padrao ?? [];
        $iguais = ($padrao['pontuacao'] ?? 'iguais') === 'iguais';

        return $iguais && $total > 0
            ? round(((float) $prova->valor_total) / $total, 4)
            : 1.0;
    }
}
