<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RespostaDetectadaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'questao_id' => $this->questao_id,
            'alternativa_detectada' => $this->alternativa_detectada,
            'alternativa_final' => $this->alternativa_final,
            'tipo_deteccao' => $this->tipo_deteccao,
            'confianca' => $this->confianca,
            'alterada_manualmente' => $this->alterada_manualmente,
        ];
    }
}
