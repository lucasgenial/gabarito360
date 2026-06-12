<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disciplina extends Model
{
    use HasUuids;

    protected $table = 'disciplinas';

    protected $guarded = [];

    public function profissionais(): HasMany
    {
        return $this->hasMany(UsuarioDisciplina::class, 'disciplina_id');
    }

    public function temasHabilidades(): HasMany
    {
        return $this->hasMany(TemaHabilidade::class, 'disciplina_id');
    }

    public function provas(): HasMany
    {
        return $this->hasMany(Prova::class, 'disciplina_id');
    }

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
