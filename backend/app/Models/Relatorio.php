<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Relatorio extends Model
{
    use HasUuids;

    protected $table = 'relatorios';

    protected $guarded = [];

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function arquivo(): BelongsTo
    {
        return $this->belongsTo(Arquivo::class, 'arquivo_id');
    }

    protected function casts(): array
    {
        return [
            'filtros' => 'array',
            'escopo' => 'array',
            'solicitado_at' => 'immutable_datetime',
            'concluido_at' => 'immutable_datetime',
            'expira_at' => 'immutable_datetime',
        ];
    }
}
