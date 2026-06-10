<?php

namespace App\Models;

use App\Enums\AccessScope;
use App\Enums\StatusEnum;
use Database\Factories\PerfilFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perfil extends Model
{
    /** @use HasFactory<PerfilFactory> */
    use HasFactory, HasUuids;

    protected $table = 'perfis';

    /** @var list<string> */
    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'escopo_permitido',
        'sistema',
        'status',
    ];

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'perfil_permissoes', 'perfil_id', 'permissao_id')
            ->withPivot('created_at');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usuario_perfis', 'perfil_id', 'usuario_id')
            ->withPivot([
                'id',
                'nucleo_id',
                'escola_id',
                'concedido_por',
                'inicio_at',
                'fim_at',
                'created_at',
            ]);
    }

    public function usuarioVinculos(): HasMany
    {
        return $this->hasMany(UsuarioPerfil::class, 'perfil_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'escopo_permitido' => AccessScope::class,
            'sistema' => 'boolean',
            'status' => StatusEnum::class,
        ];
    }
}
