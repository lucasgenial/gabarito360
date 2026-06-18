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
        'data_nascimento',
        'telefone',
        'data_ingresso',
        'formacao_academica',
        'especializacao',
        'registro_profissional',
        'observacoes',
        'forcar_troca_senha',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'ativo'               => 'boolean',
        'password'            => 'hashed',
        'ultimo_acesso'       => 'datetime',
        'data_nascimento'     => 'date',
        'data_ingresso'       => 'date',
        'forcar_troca_senha'  => 'boolean',
    ];

    public function escopos()
    {
        return $this->hasMany(UsuarioEscopo::class, 'usuario_id');
    }
}
