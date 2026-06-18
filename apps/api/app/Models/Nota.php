<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    protected $table = 'notas';

    protected $fillable = [
        'cartao_id', 'prova_id', 'aluno_id', 'turma_id',
        'acertos', 'total_questoes', 'nota_final', 'status_aprovacao', 'acertos_por_tema',
    ];

    protected $casts = [
        'acertos_por_tema' => 'array',
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
