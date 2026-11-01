<?php

namespace App\Rules;

use App\Support\DadoProtegido;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UnicoProtegido implements ValidationRule
{
    public function __construct(private string $tabela, private string $campo, private ?int $ignorar = null, private ?int $espaco = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') return;
        $q = DB::table($this->tabela)->where($this->campo . '_hash', DadoProtegido::hash($value, $this->campo));
        if ($this->espaco !== null) $q->where('espaco_id', $this->espaco);
        if ($this->ignorar !== null) $q->where('id', '!=', $this->ignorar);
        if ($q->exists()) $fail('Este dado já está cadastrado.');
    }
}
