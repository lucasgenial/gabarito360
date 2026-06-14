<?php

namespace App\Models;

use App\Enums\IntegracaoStatus;
use Database\Factories\IntegracaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integracao extends Model
{
    /** @use HasFactory<IntegracaoFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'integracoes';

    /** @var list<string> */
    protected $fillable = [
        'escopo',
        'nucleo_id',
        'escola_id',
        'chave',
        'nome',
        'descricao',
        'status',
        'ultima_execucao',
        'ultima_sincronizacao',
        'erros',
        'ativa',
    ];

    public function credenciais(): HasMany
    {
        return $this->hasMany(CredencialIntegracao::class, 'integracao_id');
    }

    public function nucleo(): BelongsTo
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => IntegracaoStatus::class,
            'ultima_execucao' => 'immutable_datetime',
            'ultima_sincronizacao' => 'immutable_datetime',
            'erros' => 'array',
            'ativa' => 'boolean',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
