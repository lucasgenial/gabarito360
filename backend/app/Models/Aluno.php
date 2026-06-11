<?php

namespace App\Models;

use App\Enums\StatusEnum;
use Database\Factories\AlunoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aluno extends Model
{
    /** @use HasFactory<AlunoFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'alunos';

    /** @var list<string> */
    protected $fillable = [
        'escola_id',
        'matricula',
        'codigo_interno',
        'nome',
        'data_nascimento',
        'documento',
        'status',
        'observacoes',
    ];

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function matriculasTurmas(): HasMany
    {
        return $this->hasMany(MatriculaTurma::class, 'aluno_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data_nascimento' => 'immutable_date',
            'status' => StatusEnum::class,
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
