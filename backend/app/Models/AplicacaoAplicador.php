<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AplicacaoAplicador extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'aplicacao_aplicadores';

    protected $guarded = ['chave_vigente'];

    public function aplicacao(): BelongsTo
    {
        return $this->belongsTo(Aplicacao::class, 'aplicacao_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    protected function casts(): array
    {
        return [
            'inicio_at' => 'immutable_datetime',
            'fim_at' => 'immutable_datetime',
        ];
    }
}
