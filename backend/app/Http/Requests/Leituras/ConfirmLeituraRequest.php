<?php

namespace App\Http\Requests\Leituras;

use App\Models\LeituraCartao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ConfirmLeituraRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reading = $this->route('leitura');

        return $reading instanceof LeituraCartao
            && ($this->user()?->can('confirmReading', $reading->aplicacao) ?? false);
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $key = $this->header('Idempotency-Key');

            if (! is_string($key) || trim($key) === '' || strlen($key) > 80) {
                $validator->errors()->add('Idempotency-Key', 'Informe uma chave de idempotencia valida no cabecalho.');
            }
        });
    }

    public function idempotencyKey(): string
    {
        return trim((string) $this->header('Idempotency-Key'));
    }
}
