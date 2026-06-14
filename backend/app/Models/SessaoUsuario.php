<?php

namespace App\Models;

use Database\Factories\SessaoUsuarioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessaoUsuario extends Model
{
    /** @use HasFactory<SessaoUsuarioFactory> */
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    public const CREATED_AT = 'criado_em';

    protected $table = 'sessoes_usuarios';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'personal_access_token_id',
        'dispositivo',
        'ip',
        'manter_conectado',
        'criado_em',
        'ultimo_acesso_at',
        'encerrado_at',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }

    /**
     * @param  Builder<SessaoUsuario>  $query
     */
    public function scopeAtivas(Builder $query): void
    {
        $query->whereNull('encerrado_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'manter_conectado' => 'boolean',
            'criado_em' => 'immutable_datetime',
            'ultimo_acesso_at' => 'immutable_datetime',
            'encerrado_at' => 'immutable_datetime',
        ];
    }
}
