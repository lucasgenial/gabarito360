<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escola extends Model
{
    protected $table = 'escolas';

    protected $fillable = [
        'nucleo_id', 'nome', 'inep', 'tipo_rede',
        'logradouro', 'cidade', 'uf', 'telefone', 'email', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public function nucleo()
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class, 'escola_id');
    }

    public function provas()
    {
        return $this->hasMany(Prova::class, 'escola_id');
    }
}
