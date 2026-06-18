<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visita extends Model
{
    protected $table = 'visitas';

    protected $fillable = ['nucleo_id', 'escola_id', 'agendado_por', 'data_visita', 'tipo', 'urgencia'];

    protected $casts = ['data_visita' => 'date'];

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'escola_id');
    }

    public function nucleo()
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }
}
