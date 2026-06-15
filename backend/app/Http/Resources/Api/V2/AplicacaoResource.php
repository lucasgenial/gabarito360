<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Aplicacao;
use App\Services\Applications\ApplicationMetrics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Aplicacao
 *
 * Espera `prova`, `turma` e `escola` carregados. Chame `withMetrics()` para
 * incluir os contadores de progresso (uma consulta por aplicação).
 */
class AplicacaoResource extends JsonResource
{
    private bool $includeMetrics = false;

    public function withMetrics(): self
    {
        $this->includeMetrics = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'status' => $this->status,
            'prova' => ['id' => $this->prova_id, 'titulo' => $this->prova?->titulo],
            'turma' => ['id' => $this->turma_id, 'nome' => $this->turma?->nome],
            'escola' => ['id' => $this->escola_id, 'nome' => $this->escola?->nome],
            'inicio_previsto_at' => $this->inicio_previsto_at?->toIso8601String(),
            'fim_previsto_at' => $this->fim_previsto_at?->toIso8601String(),
            'iniciada_at' => $this->iniciada_at?->toIso8601String(),
            'finalizada_at' => $this->finalizada_at?->toIso8601String(),
        ];

        if ($this->includeMetrics) {
            $data['metricas'] = app(ApplicationMetrics::class)->for($this->resource);
        }

        return $data;
    }
}
