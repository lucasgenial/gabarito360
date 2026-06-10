<?php

namespace App\Models;

use Database\Factories\PermissaoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permissao extends Model
{
    /** @use HasFactory<PermissaoFactory> */
    use HasFactory, HasUuids;

    protected $table = 'permissoes';

    /** @var list<string> */
    protected $fillable = [
        'codigo',
        'descricao',
    ];

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'perfil_permissoes', 'permissao_id', 'perfil_id')
            ->withPivot('created_at');
    }
}
