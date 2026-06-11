<?php

namespace App\Models;

use App\Enums\StatusEnum;
use Database\Factories\TurmaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Turma extends Model
{
    /** @use HasFactory<TurmaFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'turmas';

    /** @var list<string> */
    protected $fillable = [
        'escola_id',
        'codigo',
        'nome',
        'serie_ano',
        'turno',
        'ano_letivo',
        'status',
    ];

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(MatriculaTurma::class, 'turma_id');
    }

    public function aplicadores(): HasMany
    {
        return $this->hasMany(AplicadorTurma::class, 'turma_id');
    }

    public function importacoesAlunos(): HasMany
    {
        return $this->hasMany(ImportacaoAluno::class, 'turma_id');
    }

    public function provaTurmas(): HasMany
    {
        return $this->hasMany(ProvaTurma::class, 'turma_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ano_letivo' => 'integer',
            'status' => StatusEnum::class,
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
