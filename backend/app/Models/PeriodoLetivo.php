<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoLetivo extends Model
{
    use HasUuids;

    protected $table = 'periodos_letivos';

    protected $guarded = [];

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class, 'periodo_letivo_id');
    }

    protected function casts(): array
    {
        return [
            'ano' => 'integer',
            'inicio_em' => 'immutable_date',
            'fim_em' => 'immutable_date',
        ];
    }
}
