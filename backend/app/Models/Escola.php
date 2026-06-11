<?php

namespace App\Models;

use App\Enums\StatusEnum;
use Database\Factories\EscolaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Escola extends Model
{
    /** @use HasFactory<EscolaFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'escolas';

    /** @var list<string> */
    protected $fillable = [
        'nucleo_id',
        'codigo',
        'nome',
        'municipio',
        'estado',
        'endereco',
        'email',
        'telefone',
        'status',
    ];

    public function nucleo(): BelongsTo
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    public function usuarioPerfis(): HasMany
    {
        return $this->hasMany(UsuarioPerfil::class, 'escola_id');
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class, 'escola_id');
    }

    public function provas(): HasMany
    {
        return $this->hasMany(Prova::class, 'escola_id');
    }

    public function alunos(): HasMany
    {
        return $this->hasMany(Aluno::class, 'escola_id');
    }

    public function importacoesAlunos(): HasMany
    {
        return $this->hasMany(ImportacaoAluno::class, 'escola_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'escola_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'endereco' => 'array',
            'status' => StatusEnum::class,
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
