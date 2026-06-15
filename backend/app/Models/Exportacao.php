<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exportacao extends Model
{
    use HasUuids;

    protected $table = 'exportacoes';

    protected $guarded = [];

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_id');
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
            'linhas' => 'integer',
            'solicitado_at' => 'immutable_datetime',
            'concluido_at' => 'immutable_datetime',
            'expira_at' => 'immutable_datetime',
        ];
    }
}
