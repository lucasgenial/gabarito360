<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CartaoResposta extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cartoes_resposta';

    protected $guarded = ['chave_vigente'];

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class, 'aluno_id');
    }

    public function aplicacao(): BelongsTo
    {
        return $this->belongsTo(Aplicacao::class, 'aplicacao_id');
    }

    public function leituras(): HasMany
    {
        return $this->hasMany(LeituraCartao::class, 'cartao_resposta_id');
    }

    protected function casts(): array
    {
        return ['codigo_sistema_afixado' => 'boolean'];
    }
}
