<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoAcesso extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'historicos_acesso';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'evento',
        'ip',
        'user_agent',
        'sessao_id',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sessao(): BelongsTo
    {
        return $this->belongsTo(SessaoUsuario::class, 'sessao_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
        ];
    }
}
