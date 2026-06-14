<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoCadastro extends Model
{
    use HasUuids;

    protected $table = 'solicitacoes_cadastro';

    /** @var list<string> */
    protected $fillable = [
        'nome',
        'documento_hash',
        'documento_mascarado',
        'email',
        'perfil_codigo',
        'status',
        'consentimento_id',
        'metadados',
    ];

    public function consentimento(): BelongsTo
    {
        return $this->belongsTo(Consentimento::class, 'consentimento_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadados' => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
