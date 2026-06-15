<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoliticaRetencao extends Model
{
    use HasUuids;

    protected $table = 'politicas_retencao';

    protected $guarded = [];

    public function execucoes(): HasMany
    {
        return $this->hasMany(ExecucaoDescarte::class, 'politica_retencao_id');
    }

    protected function casts(): array
    {
        return [
            'reter_dias' => 'integer',
            'ativo' => 'boolean',
        ];
    }
}
