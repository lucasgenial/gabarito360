<?php

namespace App\Http\Requests\Api\V2\ProvaTurmas;

use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;

class ListProvaTurmasRequest extends FormRequest
{
    public function authorize(): bool
    {
        $prova = $this->route('prova');

        return $prova instanceof Prova
            && ($this->user()?->can('viewClassLinks', $prova) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
