<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GabaritoResposta extends Model
{
    use HasUuids;

    protected $table = 'gabarito_respostas';

    /** @var list<string> */
    protected $fillable = [
        'prova_id',
        'gabarito_oficial_id',
        'questao_id',
        'alternativa_correta',
        'anulada',
        'peso',
    ];

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function gabaritoOficial(): BelongsTo
    {
        return $this->belongsTo(GabaritoOficial::class, 'gabarito_oficial_id');
    }

    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class, 'questao_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'anulada' => 'boolean',
            'peso' => 'decimal:4',
        ];
    }
}
