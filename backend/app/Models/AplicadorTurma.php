<?php

namespace App\Models;

use App\Enums\AplicadorTurmaPapel;
use Database\Factories\AplicadorTurmaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AplicadorTurma extends Model
{
    /** @use HasFactory<AplicadorTurmaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'aplicadores_turmas';

    /** @var list<string> */
    protected $fillable = [
        'turma_id',
        'usuario_id',
        'papel',
        'inicio_em',
        'fim_em',
        'vinculado_por',
    ];

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function vinculadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vinculado_por');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'papel' => AplicadorTurmaPapel::class,
            'inicio_em' => 'immutable_date',
            'fim_em' => 'immutable_date',
        ];
    }
}
