<?php

namespace App\Models;

use App\Enums\StatusEnum;
use Database\Factories\NucleoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nucleo extends Model
{
    /** @use HasFactory<NucleoFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'nucleos';

    /** @var list<string> */
    protected $fillable = [
        'codigo',
        'nome',
        'municipio',
        'estado',
        'email',
        'telefone',
        'status',
    ];

    public function usuarioPerfis(): HasMany
    {
        return $this->hasMany(UsuarioPerfil::class, 'nucleo_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'nucleo_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => StatusEnum::class,
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
