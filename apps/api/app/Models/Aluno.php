<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aluno extends Model
{
    protected $table = 'alunos';

    protected $fillable = [
        'turma_id', 'nome', 'matricula', 'cpf', 'data_nascimento', 'genero', 'foto_path', 'nome_responsavel', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public function turma()
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'aluno_id');
    }
}
