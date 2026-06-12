<?php

namespace App\Http\Requests\Aplicacoes;

use Illuminate\Foundation\Http\FormRequest;

class RunAplicacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('run', $this->route('aplicacao')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
