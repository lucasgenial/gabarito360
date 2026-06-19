<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    protected $table = 'pagamentos';

    public $timestamps = false;

    protected $fillable = [
        'assinatura_id', 'valor', 'status', 'metodo',
        'gateway_payment_id', 'pago_em',
    ];

    protected $casts = [
        'valor'      => 'decimal:2',
        'pago_em'    => 'datetime',
        'created_at' => 'datetime',
    ];

    public function assinatura()
    {
        return $this->belongsTo(Assinatura::class, 'assinatura_id');
    }
}
