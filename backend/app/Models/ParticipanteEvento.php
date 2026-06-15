<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipanteEvento extends Model
{
    use HasUuids;

    protected $table = 'participantes_eventos';

    protected $guarded = [];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoAgenda::class, 'evento_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    protected function casts(): array
    {
        return [
            'respondido_at' => 'immutable_datetime',
        ];
    }
}
