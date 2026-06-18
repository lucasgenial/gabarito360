<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SincronizacaoSeges extends Model
{
    protected $table = 'sincronizacoes_seges';

    public $timestamps = false;

    protected $fillable = [
        'status', 'iniciado_em', 'concluido_em', 'duracao_minutos', 'detalhes',
    ];

    protected $casts = [
        'iniciado_em'  => 'datetime',
        'concluido_em' => 'datetime',
        'detalhes'     => 'array',
    ];
}
