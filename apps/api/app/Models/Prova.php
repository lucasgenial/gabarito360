<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prova extends Model
{
    protected $table = 'provas';

    protected $fillable = [
        'escola_id', 'criado_por', 'titulo', 'disciplina', 'serie',
        'num_questoes', 'num_alternativas', 'nota_maxima', 'tipo_pontuacao',
        'anular_se_todas', 'opcao_lingua', 'gerar_cartao_pdf', 'status', 'data_aplicacao',
        'correcao_fechada_em', 'correcao_fechada_por',
    ];

    protected $casts = [
        'anular_se_todas' => 'boolean',
        'opcao_lingua' => 'boolean',
        'gerar_cartao_pdf' => 'boolean',
        'data_aplicacao' => 'date',
        'correcao_fechada_em' => 'datetime',
    ];

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function cartoes()
    {
        return $this->hasMany(Cartao::class, 'prova_id');
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'prova_id');
    }
}
