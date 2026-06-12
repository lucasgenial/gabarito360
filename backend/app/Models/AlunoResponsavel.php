<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlunoResponsavel extends Model
{
    use HasUuids;

    protected $table = 'aluno_responsaveis';

    protected $guarded = ['chave_vigente'];

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class, 'aluno_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Responsavel::class, 'responsavel_id');
    }

    protected function casts(): array
    {
        return [
            'principal' => 'boolean',
            'autorizado_contato' => 'boolean',
            'inicio_em' => 'immutable_date',
            'fim_em' => 'immutable_date',
        ];
    }
}
