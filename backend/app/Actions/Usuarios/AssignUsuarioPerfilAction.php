<?php

namespace App\Actions\Usuarios;

use App\Models\User;
use App\Models\UsuarioPerfil;
use Illuminate\Support\Facades\DB;

class AssignUsuarioPerfilAction
{
    /** @param array<string, mixed> $attributes */
    public function execute(User $target, array $attributes, User $actor): UsuarioPerfil
    {
        return DB::transaction(fn (): UsuarioPerfil => $target->perfilVinculos()->create([
            ...$attributes,
            'concedido_por' => $actor->id,
            'inicio_at' => now(),
        ]));
    }
}
