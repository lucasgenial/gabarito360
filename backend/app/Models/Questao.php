<?php

namespace App\Models;

use App\Enums\QuestaoStatus;
use Database\Factories\QuestaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questao extends Model
{
    /** @use HasFactory<QuestaoFactory> */
    use HasFactory, HasUuids;

    protected $table = 'questoes';

    /** @var list<string> */
    protected $fillable = [
        'prova_id',
        'numero',
        'codigo',
        'peso_padrao',
        'status',
    ];

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function respostasOficiais(): HasMany
    {
        return $this->hasMany(GabaritoResposta::class, 'questao_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'peso_padrao' => 'decimal:4',
            'status' => QuestaoStatus::class,
        ];
    }
}
