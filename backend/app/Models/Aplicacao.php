<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aplicacao extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'aplicacoes';

    protected $guarded = [];

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function gabaritoOficial(): BelongsTo
    {
        return $this->belongsTo(GabaritoOficial::class, 'gabarito_oficial_id');
    }

    public function aplicadores(): HasMany
    {
        return $this->hasMany(AplicacaoAplicador::class, 'aplicacao_id');
    }

    public function alunos(): HasMany
    {
        return $this->hasMany(AplicacaoAluno::class, 'aplicacao_id');
    }

    public function leituras(): HasMany
    {
        return $this->hasMany(LeituraCartao::class, 'aplicacao_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'aplicacao_id');
    }

    protected function casts(): array
    {
        return [
            'inicio_previsto_at' => 'immutable_datetime',
            'fim_previsto_at' => 'immutable_datetime',
            'iniciada_at' => 'immutable_datetime',
            'finalizada_at' => 'immutable_datetime',
        ];
    }
}
