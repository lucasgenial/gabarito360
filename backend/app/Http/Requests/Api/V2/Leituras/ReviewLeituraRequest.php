<?php

namespace App\Http\Requests\Api\V2\Leituras;

use App\Models\LeituraCartao;
use Illuminate\Foundation\Http\FormRequest;

class ReviewLeituraRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reading = $this->route('leitura');

        return $reading instanceof LeituraCartao
            && ($this->user()?->can('correctReading', $reading->aplicacao) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'questao' => ['required', 'integer', 'min:1'],
            'decisao' => ['required', 'string', 'max:10', 'regex:/^([A-Za-z]|branco)$/'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }
}
