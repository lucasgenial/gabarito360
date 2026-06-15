<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventoAgenda extends Model
{
    use HasUuids;

    protected $table = 'eventos_agenda';

    protected $guarded = [];

    public function nucleo(): BelongsTo
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por_id');
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(ParticipanteEvento::class, 'evento_id');
    }

    protected function casts(): array
    {
        return [
            'inicio_at' => 'immutable_datetime',
            'fim_at' => 'immutable_datetime',
        ];
    }
}
