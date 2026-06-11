<?php

namespace App\Models;

use App\Enums\StudentImportStatus;
use Database\Factories\ImportacaoAlunoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacaoAluno extends Model
{
    /** @use HasFactory<ImportacaoAlunoFactory> */
    use HasFactory, HasUuids;

    protected $table = 'importacoes_alunos';

    /** @var list<string> */
    protected $fillable = [
        'escola_id',
        'turma_id',
        'solicitado_por',
        'confirmado_por',
        'status',
        'arquivo_disco',
        'arquivo_caminho',
        'arquivo_nome',
        'arquivo_checksum_sha256',
        'resumo',
        'erros',
        'confirmado_at',
        'processado_at',
    ];

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => StudentImportStatus::class,
            'resumo' => 'array',
            'erros' => 'array',
            'confirmado_at' => 'immutable_datetime',
            'processado_at' => 'immutable_datetime',
        ];
    }
}
