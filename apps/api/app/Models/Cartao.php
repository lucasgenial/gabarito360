<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cartao extends Model
{
    protected $table = 'cartoes';

    protected $fillable = [
        'prova_id', 'aluno_id', 'imagem_url', 'status',
        'confianca_geral', 'resolvido_por', 'resolvido_em', 'revisado_por', 'revisado_em',
    ];

    public function prova()
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function aluno()
    {
        return $this->belongsTo(Aluno::class, 'aluno_id');
    }
}
