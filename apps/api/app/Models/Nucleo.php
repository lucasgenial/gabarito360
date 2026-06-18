<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nucleo extends Model
{
    protected $table = 'nucleos';

    protected $fillable = ['rede_id', 'nome'];

    public function escolas()
    {
        return $this->hasMany(Escola::class, 'nucleo_id');
    }

    public function visitas()
    {
        return $this->hasMany(Visita::class, 'nucleo_id');
    }
}
