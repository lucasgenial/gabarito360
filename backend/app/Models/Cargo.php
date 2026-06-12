<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
    use HasUuids;

    protected $table = 'cargos';

    protected $guarded = [];

    public function lotacoes(): HasMany
    {
        return $this->hasMany(UsuarioLotacao::class, 'cargo_id');
    }

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
