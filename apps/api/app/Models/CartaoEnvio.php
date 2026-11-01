<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartaoEnvio extends Model
{
    protected $table = 'cartao_envios';

    protected $guarded = ['id'];

    protected $casts = ['resultado' => 'array', 'vinculo_id' => 'integer'];

    /** Preserva os índices T2 e a chave pública, separando a chave interna por contexto. */
    public static function chaveContextual(Usuario $usuario, string $chave, ?int $espaco): string
    {
        if ($usuario->vinculoContextoId() === null) return $chave;
        $v = \App\Support\ContextoVinculo::resolver($usuario);
        return \Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_URL,
            'g360:envio:'.$usuario->id.':'.$v->id.':'.$espaco.':'.strtolower($chave))->toString();
    }

    public function exigirContexto(Usuario $usuario): void
    {
        abort_unless((int) $this->usuario_id === (int) $usuario->id && $this->vinculo_id === $usuario->vinculoContextoId(), 404);
        if ($this->vinculo_id !== null) \App\Support\ContextoVinculo::resolver($usuario);
    }

    public function podeRepetir(): bool
    {
        // Recupera também o raro caso de interrupção entre salvar a foto e publicar na fila.
        $parado = in_array($this->status, ['na_fila', 'processando'], true)
            && $this->updated_at->lt(now()->subSeconds(max(600, 6 * (int) config('omr.timeout', 60) + 120)));

        return $this->cartao_id !== null && ($this->status === 'erro' || $parado);
    }
}
