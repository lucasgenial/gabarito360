<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $casts = [
        'email' => \App\Casts\DadoProtegido::class,
        'telefone' => \App\Casts\DadoProtegido::class,
    ];

    protected $table = 'leads';

    protected $fillable = [
        'plano_id', 'nome', 'email', 'assunto', 'telefone',
        'instituicao', 'cargo', 'licencas_estimadas', 'mensagem', 'status',
    ];
}
