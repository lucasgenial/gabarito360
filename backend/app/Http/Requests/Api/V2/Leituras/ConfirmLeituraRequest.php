<?php

namespace App\Http\Requests\Api\V2\Leituras;

use App\Models\LeituraCartao;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmLeituraRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reading = $this->route('leitura');

        return $reading instanceof LeituraCartao
            && ($this->user()?->can('confirmReading', $reading->aplicacao) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
