<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespostaDetectada extends Model
{
    protected $table = 'respostas_detectadas';

    protected $guarded = [];

    public function leituraCartao(): BelongsTo
    {
        return $this->belongsTo(LeituraCartao::class, 'leitura_cartao_id');
    }

    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class, 'questao_id');
    }

    protected function casts(): array
    {
        return [
            'confianca' => 'decimal:5',
            'alterada_manualmente' => 'boolean',
            'alterada_at' => 'immutable_datetime',
        ];
    }
}
