<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SerieAno extends Model
{
    use HasUuids;

    protected $table = 'series_anos';

    protected $guarded = [];

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class, 'serie_ano_id');
    }

    public function provas(): HasMany
    {
        return $this->hasMany(Prova::class, 'serie_ano_id');
    }

    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
            'ativo' => 'boolean',
        ];
    }
}
