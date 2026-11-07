<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turma extends Model
{
    protected $hidden = ['foto_path'];

    protected $appends = ['tem_foto'];

    public function getTemFotoAttribute(): bool { return !empty($this->attributes['foto_path']); }

    protected $table = 'turmas';

    protected $fillable = ['escola_id', 'nome', 'serie', 'turno', 'ano_letivo', 'ativo', 'sala', 'capacidade', 'etapa_ensino', 'observacoes'];

    protected $casts = ['ativo' => 'boolean', 'capacidade' => 'integer', 'avatar_id' => 'integer', 'imagem_versao' => 'integer'];

    public const ETAPAS = ['educacao_infantil', 'ensino_fundamental', 'ensino_medio', 'educacao_profissional', 'ensino_superior', 'eja', 'outra'];

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function alunos()
    {
        return $this->hasMany(Aluno::class, 'turma_id');
    }
}
