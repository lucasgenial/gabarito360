<?php

namespace App\Models;

use App\Enums\ProvaStatus;
use Database\Factories\ProvaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prova extends Model
{
    /** @use HasFactory<ProvaFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'provas';

    /** @var list<string> */
    protected $fillable = [
        'nucleo_id',
        'escola_id',
        'modelo_cartao_id',
        'codigo',
        'titulo',
        'descricao',
        'tipo',
        'nivel',
        'ano_referencia',
        'quantidade_questoes',
        'quantidade_alternativas',
        'alternativas',
        'status',
        'criado_por',
        'publicada_at',
        'finalizada_at',
    ];

    public function nucleo(): BelongsTo
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function modeloCartao(): BelongsTo
    {
        return $this->belongsTo(ModeloCartao::class, 'modelo_cartao_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function questoes(): HasMany
    {
        return $this->hasMany(Questao::class, 'prova_id');
    }

    public function gabaritosOficiais(): HasMany
    {
        return $this->hasMany(GabaritoOficial::class, 'prova_id');
    }

    public function provaTurmas(): HasMany
    {
        return $this->hasMany(ProvaTurma::class, 'prova_id');
    }

    public function ownerNucleoId(): string
    {
        if ($this->nucleo_id !== null) {
            return $this->nucleo_id;
        }

        $this->loadMissing('escola');

        return $this->escola->nucleo_id;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ano_referencia' => 'integer',
            'quantidade_questoes' => 'integer',
            'quantidade_alternativas' => 'integer',
            'alternativas' => 'array',
            'status' => ProvaStatus::class,
            'publicada_at' => 'immutable_datetime',
            'finalizada_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
