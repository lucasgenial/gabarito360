<?php

namespace App\Models;

use App\Enums\StatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Responsavel extends Model
{
    use HasUuids;

    protected $table = 'responsaveis';

    protected $guarded = [];

    public function alunos(): HasMany
    {
        return $this->hasMany(AlunoResponsavel::class, 'responsavel_id');
    }

    protected function casts(): array
    {
        return ['status' => StatusEnum::class];
    }
}
