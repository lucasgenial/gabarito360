<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnapshotIndicador extends Model
{
    use HasUuids;

    protected $table = 'snapshots_indicadores';

    protected $guarded = [];

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function nucleo(): BelongsTo
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    public function geradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerado_por_id');
    }

    protected function casts(): array
    {
        return [
            'total_resultados' => 'integer',
            'media_nota' => 'decimal:4',
            'indicadores' => 'array',
            'gerado_at' => 'immutable_datetime',
        ];
    }
}
