<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasUuids;

    protected $table = 'idempotency_keys';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'chave',
        'metodo',
        'rota',
        'request_hash',
        'status',
        'resposta_status',
        'resposta_corpo',
        'resposta_headers',
        'expira_em',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'resposta_status' => 'integer',
            'resposta_corpo' => 'array',
            'resposta_headers' => 'array',
            'expira_em' => 'immutable_datetime',
        ];
    }
}
