<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resultado extends Model
{
    use HasUuids;

    protected $table = 'resultados';

    protected $guarded = ['chave_vigente'];

    public function aplicacao(): BelongsTo
    {
        return $this->belongsTo(Aplicacao::class, 'aplicacao_id');
    }

    public function aplicacaoAluno(): BelongsTo
    {
        return $this->belongsTo(AplicacaoAluno::class, 'aplicacao_aluno_id');
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class, 'aluno_id');
    }

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function leituraCartao(): BelongsTo
    {
        return $this->belongsTo(LeituraCartao::class, 'leitura_cartao_id');
    }

    public function questoes(): HasMany
    {
        return $this->hasMany(ResultadoQuestao::class, 'resultado_id');
    }

    protected function casts(): array
    {
        return [
            'versao' => 'integer',
            'acertos' => 'integer',
            'erros' => 'integer',
            'brancos' => 'integer',
            'duplas' => 'integer',
            'anuladas' => 'integer',
            'pontuacao' => 'decimal:4',
            'nota_percentual' => 'decimal:4',
            'calculado_at' => 'immutable_datetime',
        ];
    }
}
