<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartaoEnvio extends Model
{
    protected $table = 'cartao_envios';

    protected $guarded = ['id'];

    protected $casts = ['resultado' => 'array'];

    public function podeRepetir(): bool
    {
        // Recupera também o raro caso de interrupção entre salvar a foto e publicar na fila.
        $parado = in_array($this->status, ['na_fila', 'processando'], true)
            && $this->updated_at->lt(now()->subSeconds(max(600, 6 * (int) config('omr.timeout', 60) + 120)));

        return $this->cartao_id !== null && ($this->status === 'erro' || $parado);
    }
}
