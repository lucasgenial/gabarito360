<?php

namespace App\Models;

use Database\Factories\UsuarioPerfilFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioPerfil extends Model
{
    /** @use HasFactory<UsuarioPerfilFactory> */
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'usuario_perfis';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'perfil_id',
        'nucleo_id',
        'escola_id',
        'concedido_por',
        'inicio_at',
        'fim_at',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class, 'perfil_id');
    }

    public function concedidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'concedido_por');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'inicio_at' => 'immutable_datetime',
            'fim_at' => 'immutable_datetime',
        ];
    }
}
