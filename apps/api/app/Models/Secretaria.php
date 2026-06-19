<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Secretaria extends Model
{
    protected $table = 'secretarias';

    protected $fillable = ['nome', 'tipo', 'uf', 'usuario_titular_id'];

    public function titular()
    {
        return $this->belongsTo(Usuario::class, 'usuario_titular_id');
    }
}
