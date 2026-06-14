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
        'nome_social',
        'data_nascimento',
        'documento',
        'genero',
        'foto_arquivo_id',
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

    public function fotoArquivo(): BelongsTo
    {
        return $this->belongsTo(Arquivo::class, 'foto_arquivo_id');
    }

    public function responsaveis(): HasMany
    {
        return $this->hasMany(AlunoResponsavel::class, 'aluno_id');
    }

    public function aplicacoes(): HasMany
    {
        return $this->hasMany(AplicacaoAluno::class, 'aluno_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'aluno_id');
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
