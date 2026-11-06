<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turma extends Model
{
    protected $table = 'turmas';

    protected $fillable = ['escola_id', 'nome', 'serie', 'turno', 'ano_letivo', 'ativo', 'sala', 'capacidade', 'etapa_ensino', 'observacoes'];

    protected $casts = ['ativo' => 'boolean', 'capacidade' => 'integer'];

    public const ETAPAS = ['educacao_infantil', 'ensino_fundamental', 'ensino_medio', 'educacao_profissional', 'ensino_superior', 'eja', 'outra'];

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function alunos()
    {
        return $this->hasMany(Aluno::class, 'turma_id');
    }
}
