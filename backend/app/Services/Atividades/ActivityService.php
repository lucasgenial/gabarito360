<?php

namespace App\Services\Atividades;

use App\Models\AtividadeRecente;

/**
 * Registra entradas no feed de atividade (append-only) consumido pelos painéis.
 */
class ActivityService
{
    /**
     * @param  array{ator_id?: ?string, nucleo_id?: ?string, escola_id?: ?string, sujeito_tipo?: ?string, sujeito_id?: ?string, dados?: array<string, mixed>}  $opts
     */
    public function record(string $tipo, string $descricao, array $opts = []): AtividadeRecente
    {
        return AtividadeRecente::query()->create([
            'tipo' => $tipo,
            'descricao' => $descricao,
            'ator_id' => $opts['ator_id'] ?? null,
            'nucleo_id' => $opts['nucleo_id'] ?? null,
            'escola_id' => $opts['escola_id'] ?? null,
            'sujeito_tipo' => $opts['sujeito_tipo'] ?? null,
            'sujeito_id' => $opts['sujeito_id'] ?? null,
            'dados' => $opts['dados'] ?? null,
        ]);
    }
}
