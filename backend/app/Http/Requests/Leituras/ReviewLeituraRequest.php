<?php

namespace App\Http\Requests\Leituras;

use App\Models\LeituraCartao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewLeituraRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reading = $this->route('leitura');

        return $reading instanceof LeituraCartao
            && ($this->user()?->can('correctReading', $reading->aplicacao) ?? false);
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'respostas' => ['required', 'array', 'min:1', 'max:500'],
            'respostas.*.questao_id' => ['required', 'uuid', 'distinct'],
            'respostas.*.alternativa_final' => ['nullable', 'string', 'max:10'],
            'respostas.*.tipo_deteccao' => ['nullable', Rule::in(['marcada', 'branco', 'dupla', 'ambigua'])],
        ];
    }
}
