<?php

namespace App\Models;

use App\Enums\MatriculaTurmaStatus;
use Database\Factories\MatriculaTurmaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatriculaTurma extends Model
{
    /** @use HasFactory<MatriculaTurmaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'matriculas_turmas';

    /** @var list<string> */
    protected $fillable = [
        'aluno_id',
        'turma_id',
        'ano_letivo',
        'numero_chamada',
        'status',
        'inicio_em',
        'fim_em',
    ];

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class, 'aluno_id');
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ano_letivo' => 'integer',
            'status' => MatriculaTurmaStatus::class,
            'inicio_em' => 'immutable_date',
            'fim_em' => 'immutable_date',
        ];
    }
}
