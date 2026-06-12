<?php

namespace App\Models;

use App\Enums\GabaritoOficialStatus;
use Database\Factories\GabaritoOficialFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GabaritoOficial extends Model
{
    /** @use HasFactory<GabaritoOficialFactory> */
    use HasFactory, HasUuids;

    protected $table = 'gabaritos_oficiais';

    /** @var list<string> */
    protected $fillable = [
        'prova_id',
        'versao',
        'status',
        'justificativa',
        'criado_por',
        'publicado_por',
        'publicado_at',
    ];

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function publicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicado_por');
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(GabaritoResposta::class, 'gabarito_oficial_id');
    }

    public function aplicacoes(): HasMany
    {
        return $this->hasMany(Aplicacao::class, 'gabarito_oficial_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'gabarito_oficial_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'versao' => 'integer',
            'status' => GabaritoOficialStatus::class,
            'publicado_at' => 'immutable_datetime',
        ];
    }
}
