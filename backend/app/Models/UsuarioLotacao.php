<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioLotacao extends Model
{
    use HasUuids;

    protected $table = 'usuario_lotacoes';

    protected $guarded = ['chave_vigente'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function nucleo(): BelongsTo
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    protected function casts(): array
    {
        return [
            'inicio_em' => 'immutable_date',
            'fim_em' => 'immutable_date',
            'principal' => 'boolean',
        ];
    }
}
