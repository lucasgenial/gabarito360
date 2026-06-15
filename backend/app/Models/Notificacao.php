<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacao extends Model
{
    use HasUuids;

    protected $table = 'notificacoes';

    protected $guarded = [];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function lida(): bool
    {
        return $this->lida_at !== null;
    }

    protected function casts(): array
    {
        return [
            'dados' => 'array',
            'lida_at' => 'immutable_datetime',
        ];
    }
}
