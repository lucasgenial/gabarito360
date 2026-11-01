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
        'remember_token', 'email_hash', 'cpf_hash',
    ];

    protected $casts = [
        'email' => \App\Casts\DadoProtegido::class . ':1',
        'cpf' => \App\Casts\DadoProtegido::class . ':1',
        'telefone' => \App\Casts\DadoProtegido::class,

        'ativo'               => 'boolean',
        'password'            => 'hashed',
        'ultimo_acesso'       => 'datetime',
        'data_nascimento'     => 'date',
        'data_ingresso'       => 'date',
        'forcar_troca_senha'  => 'boolean',
    ];

    public function scopePorEmail($query, string $email)
    {
        return $query->where('email_hash', \App\Support\DadoProtegido::hash($email, 'email'));
    }

    public function escopos()
    {
        return $this->hasMany(UsuarioEscopo::class, 'usuario_id');
    }

    public function vinculos()
    {
        return $this->hasMany(UsuarioVinculo::class, 'usuario_id');
    }
}
