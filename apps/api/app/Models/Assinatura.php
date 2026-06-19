<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assinatura extends Model
{
    protected $table = 'assinaturas';

    protected $fillable = [
        'plano_id', 'titular_id', 'rede_id', 'secretaria_id',
        'licencas_contratadas', 'status', 'gateway_subscription_id',
        'trial_termina_em', 'proxima_cobranca_em',
    ];

    protected $casts = [
        'trial_termina_em'    => 'datetime',
        'proxima_cobranca_em' => 'datetime',
    ];

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public function titular()
    {
        return $this->belongsTo(Usuario::class, 'titular_id');
    }

    public function rede()
    {
        return $this->belongsTo(Rede::class, 'rede_id');
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class, 'assinatura_id')->orderByDesc('created_at');
    }

    public function estaAtiva(): bool
    {
        return in_array($this->status, ['trial', 'ativa']);
    }
}
