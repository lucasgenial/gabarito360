<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits)) {
            $fail('O :attribute informado nao e um CPF valido.');

            return;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;

            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $digits[$i] * (($t + 1) - $i);
            }

            $check = ((10 * $sum) % 11) % 10;

            if ((int) $digits[$t] !== $check) {
                $fail('O :attribute informado nao e um CPF valido.');

                return;
            }
        }
    }
}
