<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AplicacaoAluno extends Model
{
    use HasUuids;

    protected $table = 'aplicacao_alunos';

    protected $guarded = [];

    public function aplicacao(): BelongsTo
    {
        return $this->belongsTo(Aplicacao::class, 'aplicacao_id');
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class, 'aluno_id');
    }

    public function matriculaTurma(): BelongsTo
    {
        return $this->belongsTo(MatriculaTurma::class, 'matricula_turma_id');
    }

    public function resultadoVigente(): BelongsTo
    {
        return $this->belongsTo(Resultado::class, 'resultado_vigente_id');
    }

    public function leituras(): HasMany
    {
        return $this->hasMany(LeituraCartao::class, 'aplicacao_aluno_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'aplicacao_aluno_id');
    }

    protected function casts(): array
    {
        return ['confirmado_at' => 'immutable_datetime'];
    }
}
