<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoLgpd extends Model
{
    use HasUuids;

    protected $table = 'solicitacoes_lgpd';

    protected $guarded = [];

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    protected function casts(): array
    {
        return [
            'prazo_at' => 'immutable_date',
            'concluida_at' => 'immutable_datetime',
        ];
    }
}
