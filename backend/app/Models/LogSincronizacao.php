<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogSincronizacao extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'logs_sincronizacao';

    protected $guarded = [];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function aplicacao(): BelongsTo
    {
        return $this->belongsTo(Aplicacao::class, 'aplicacao_id');
    }

    protected function casts(): array
    {
        return [
            'tentativas' => 'integer',
            'processado_at' => 'immutable_datetime',
        ];
    }
}
