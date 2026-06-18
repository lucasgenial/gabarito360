<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'perfil',
        'nome',
        'email',
        'cpf',
        'password',
        'ativo',
        'escola_nome',
        'ultimo_acesso',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'ativo'         => 'boolean',
        'password'      => 'hashed',
        'ultimo_acesso' => 'datetime',
    ];

    public function escopos()
    {
        return $this->hasMany(UsuarioEscopo::class, 'usuario_id');
    }
}
