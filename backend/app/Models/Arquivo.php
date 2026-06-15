<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Arquivo extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'arquivos';

    protected $guarded = [];

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por_id');
    }

    protected function casts(): array
    {
        return [
            'tamanho_bytes' => 'integer',
            'reter_ate' => 'immutable_datetime',
            'descartado_at' => 'immutable_datetime',
        ];
    }
}
