<?php

namespace App\Models;

use App\Casts\DadoProtegido;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    public const TIPOS = ['professor', 'escola', 'nucleo', 'rede', 'secretaria', 'governo'];
    public const MODALIDADES = ['pagante', 'presenteado', 'teste', 'interno'];

    protected $table = 'clientes';
    protected $guarded = ['*'];
    protected $hidden = ['documento', 'documento_hash', 'contato_nome', 'contato_email', 'contato_telefone', 'contrato_referencia'];
    protected $casts = [
        'documento' => DadoProtegido::class.':1',
        'contato_nome' => DadoProtegido::class,
        'contato_email' => DadoProtegido::class,
        'contato_telefone' => DadoProtegido::class,
        'contrato_referencia' => DadoProtegido::class,
    ];
}
