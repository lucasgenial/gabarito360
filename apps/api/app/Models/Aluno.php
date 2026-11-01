<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aluno extends Model
{
    protected $hidden = ['cpf_hash', 'foto_path'];

    protected $table = 'alunos';

    protected $fillable = [
        'avatar_id', 'turma_id', 'nome', 'matricula', 'cpf', 'data_nascimento', 'genero', 'nome_responsavel', 'ativo',
    ];

    protected $casts = [
        'avatar_id' => 'integer',
        'cpf' => \App\Casts\DadoProtegido::class . ':1',
        'ativo' => 'boolean',
    ];

    public function turma()
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function toArray(): array
    {
        $dados = parent::toArray();
        return $this->espaco_tipo === 'pessoal' ? \App\Support\EspacoPessoal::dadosMinimos($dados) : $dados;
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'aluno_id');
    }
}
