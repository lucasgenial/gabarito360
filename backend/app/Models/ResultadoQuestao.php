<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadoQuestao extends Model
{
    protected $table = 'resultado_questoes';

    protected $guarded = [];

    public function resultado(): BelongsTo
    {
        return $this->belongsTo(Resultado::class, 'resultado_id');
    }

    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class, 'questao_id');
    }

    protected function casts(): array
    {
        return [
            'pontuacao' => 'decimal:4',
            'tema_snapshot' => 'array',
        ];
    }
}
