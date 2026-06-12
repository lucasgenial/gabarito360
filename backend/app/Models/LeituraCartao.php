<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeituraCartao extends Model
{
    use HasUuids;

    protected $table = 'leituras_cartao';

    protected $guarded = [];

    public function aplicacao(): BelongsTo
    {
        return $this->belongsTo(Aplicacao::class, 'aplicacao_id');
    }

    public function aplicacaoAluno(): BelongsTo
    {
        return $this->belongsTo(AplicacaoAluno::class, 'aplicacao_aluno_id');
    }

    public function cartaoResposta(): BelongsTo
    {
        return $this->belongsTo(CartaoResposta::class, 'cartao_resposta_id');
    }

    public function respostasDetectadas(): HasMany
    {
        return $this->hasMany(RespostaDetectada::class, 'leitura_cartao_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'leitura_cartao_id');
    }

    protected function casts(): array
    {
        return [
            'confianca_geral' => 'decimal:5',
            'requer_revisao' => 'boolean',
            'alertas' => 'array',
            'confirmada_at' => 'immutable_datetime',
            'cancelada_at' => 'immutable_datetime',
        ];
    }
}
