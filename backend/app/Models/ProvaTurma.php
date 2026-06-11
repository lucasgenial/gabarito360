<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvaTurma extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'prova_turmas';

    /** @var list<string> */
    protected $fillable = [
        'prova_id',
        'turma_id',
        'data_prevista',
        'vinculado_por',
    ];

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function vinculadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vinculado_por');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data_prevista' => 'immutable_date',
        ];
    }
}
