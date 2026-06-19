<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    protected $table = 'planos';

    protected $fillable = [
        'nome', 'publico', 'modo', 'preco_fixo', 'preco_por_licenca',
        'licencas_min', 'licencas_max', 'periodicidade', 'limites', 'ativo',
    ];

    protected $casts = [
        'preco_fixo'        => 'decimal:2',
        'preco_por_licenca' => 'decimal:2',
        'limites'           => 'array',
        'ativo'             => 'boolean',
    ];

    public function isAutoatendimento(): bool
    {
        return $this->modo === 'autoatendimento';
    }

    public function valorPara(int $licencas): float
    {
        if ($this->preco_por_licenca !== null) {
            return (float) $this->preco_por_licenca * $licencas;
        }

        return (float) ($this->preco_fixo ?? 0);
    }

    public function assinaturas()
    {
        return $this->hasMany(Assinatura::class, 'plano_id');
    }
}
