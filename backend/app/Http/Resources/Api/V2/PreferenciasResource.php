<?php

namespace App\Http\Resources\Api\V2;

use App\Models\PreferenciaUsuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PreferenciaUsuario
 */
class PreferenciasResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tema' => $this->temaEn(),
            'idioma' => $this->idioma,
            'regiao' => $this->regiao,
            'acessibilidade' => $this->acessibilidade ?? [
                'contraste_alto' => (bool) $this->contraste_alto,
                'reduzir_movimento' => (bool) $this->reduzir_movimento,
            ],
            'notificacoes' => $this->notificacoes ?? (object) [],
        ];
    }

    private function temaEn(): string
    {
        if ($this->tema_sistema) {
            return 'system';
        }

        if ($this->contraste_alto) {
            return 'contrast';
        }

        return $this->tema === 'escuro' ? 'dark' : 'light';
    }
}
