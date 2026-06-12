<?php

namespace App\Models;

use App\Enums\QuestaoStatus;
use Database\Factories\QuestaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'enunciado',
        'peso_padrao',
        'metadados',
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

    public function temasHabilidades(): BelongsToMany
    {
        return $this->belongsToMany(TemaHabilidade::class, 'questao_temas', 'questao_id', 'tema_habilidade_id')
            ->withPivot('principal');
    }

    public function respostasDetectadas(): HasMany
    {
        return $this->hasMany(RespostaDetectada::class, 'questao_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoQuestao::class, 'questao_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'peso_padrao' => 'decimal:4',
            'metadados' => 'array',
            'status' => QuestaoStatus::class,
        ];
    }
}
