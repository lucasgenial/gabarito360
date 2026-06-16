<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioEscopo extends Model
{
    protected $table = 'usuario_escopos';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'escopo_tipo',
        'escopo_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
