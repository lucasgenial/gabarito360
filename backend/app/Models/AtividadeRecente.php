<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtividadeRecente extends Model
{
    use HasUuids;

    protected $table = 'atividades_recentes';

    /** Feed append-only: registra apenas a data de criação. */
    public const UPDATED_AT = null;

    protected $guarded = [];

    public function ator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ator_id');
    }

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    protected function casts(): array
    {
        return [
            'dados' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
