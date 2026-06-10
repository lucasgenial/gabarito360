<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Auditoria extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'auditorias';

    /** @var list<string> */
    protected $fillable = [
        'request_id',
        'usuario_id',
        'nucleo_id',
        'escola_id',
        'acao',
        'entidade_tipo',
        'entidade_id',
        'dados_anteriores',
        'dados_novos',
        'metadados',
        'ip',
        'user_agent',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'dados_anteriores' => 'array',
            'dados_novos' => 'array',
            'metadados' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit records are immutable.'));
        static::deleting(fn () => throw new LogicException('Audit records are immutable.'));
    }
}
