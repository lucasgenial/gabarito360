<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanoUso extends Model
{
    use HasUuids;

    protected $table = 'planos_uso';

    protected $guarded = [];

    public function nucleo(): BelongsTo
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    protected function casts(): array
    {
        return [
            'limites' => 'array',
            'uso' => 'array',
            'ciclo_inicio' => 'immutable_date',
            'ciclo_fim' => 'immutable_date',
            'atualizado_em' => 'immutable_datetime',
        ];
    }
}
