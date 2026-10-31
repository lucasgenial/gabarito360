<?php

namespace App\Casts;

use App\Support\DadoProtegido as Indice;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class DadoProtegido implements CastsAttributes
{
    public function __construct(private bool $indexar = false) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Crypt::decryptString(str_starts_with($value, 'g360:v1:') ? substr($value, 8) : $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $valor = $value === null || $value === '' ? null : (string) $value;
        $dados = [$key => $valor === null ? null : 'g360:v1:'.Crypt::encryptString($valor)];
        if ($this->indexar) $dados[$key . '_hash'] = Indice::hash($valor, $key);
        return $dados;
    }
}
