<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioDisciplina extends Model
{
    use HasUuids;

    protected $table = 'usuario_disciplinas';

    protected $guarded = ['chave_vigente'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class, 'disciplina_id');
    }

    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    protected function casts(): array
    {
        return [
            'inicio_em' => 'immutable_date',
            'fim_em' => 'immutable_date',
        ];
    }
}
