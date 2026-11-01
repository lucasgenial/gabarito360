<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Estrutura V1. Ainda não substitui o contexto legado de autorização. */
class UsuarioVinculo extends Model
{
    protected $table = 'usuario_vinculos';

    // Escrita futura por serviço validado, nunca por mass assignment do cliente.
    protected $guarded = ['*'];

    protected $casts = [
        'inicio_em' => 'immutable_datetime',
        'fim_em' => 'immutable_datetime',
        'versao_autorizacao' => 'integer',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
