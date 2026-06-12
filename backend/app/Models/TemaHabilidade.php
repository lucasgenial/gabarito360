<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TemaHabilidade extends Model
{
    use HasUuids;

    protected $table = 'temas_habilidades';

    protected $guarded = [];

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }

    public function questoes(): BelongsToMany
    {
        return $this->belongsToMany(Questao::class, 'questao_temas', 'tema_habilidade_id', 'questao_id')
            ->withPivot('principal');
    }

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
