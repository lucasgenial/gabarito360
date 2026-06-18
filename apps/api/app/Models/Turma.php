<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turma extends Model
{
    protected $table = 'turmas';

    protected $fillable = ['escola_id', 'nome', 'serie', 'turno', 'ano_letivo', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function alunos()
    {
        return $this->hasMany(Aluno::class, 'turma_id');
    }
}
