<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Consentimento extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'consentimentos';

    /** @var list<string> */
    protected $fillable = [
        'titular_tipo',
        'titular_id',
        'finalidade',
        'concedido',
        'versao_termo',
        'ip',
        'user_agent',
        'concedido_em',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'concedido' => 'boolean',
            'concedido_em' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
