<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreferenciaUsuario extends Model
{
    public $incrementing = false;

    protected $table = 'preferencias_usuario';

    protected $primaryKey = 'usuario_id';

    protected $keyType = 'string';

    protected $guarded = [];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    protected function casts(): array
    {
        return [
            'contraste_alto' => 'boolean',
            'reduzir_movimento' => 'boolean',
        ];
    }
}
