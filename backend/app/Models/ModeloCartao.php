<?php

namespace App\Models;

use App\Enums\ModeloCartaoOrigemCodigo;
use App\Enums\ModeloCartaoStatus;
use App\Enums\ModeloCartaoTipoCodigo;
use Database\Factories\ModeloCartaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModeloCartao extends Model
{
    /** @use HasFactory<ModeloCartaoFactory> */
    use HasFactory, HasUuids;

    protected $table = 'modelos_cartao';

    /** @var list<string> */
    protected $fillable = [
        'nucleo_id',
        'nome',
        'versao',
        'quantidade_questoes',
        'quantidade_alternativas',
        'alternativas',
        'tipo_codigo',
        'origem_codigo',
        'configuracao_omr',
        'artefato_checksum_sha256',
        'status',
        'criado_por',
        'homologado_por',
        'homologado_at',
    ];

    public function nucleo(): BelongsTo
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function homologadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homologado_por');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'versao' => 'integer',
            'quantidade_questoes' => 'integer',
            'quantidade_alternativas' => 'integer',
            'alternativas' => 'array',
            'tipo_codigo' => ModeloCartaoTipoCodigo::class,
            'origem_codigo' => ModeloCartaoOrigemCodigo::class,
            'configuracao_omr' => 'array',
            'status' => ModeloCartaoStatus::class,
            'homologado_at' => 'immutable_datetime',
        ];
    }
}
