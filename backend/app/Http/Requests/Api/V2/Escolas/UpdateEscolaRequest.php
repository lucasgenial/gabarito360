<?php

namespace App\Http\Requests\Api\V2\Escolas;

use App\Models\Escola;

class UpdateEscolaRequest extends EscolaApiRequest
{
    public function authorize(): bool
    {
        $escola = $this->route('escola');

        return $escola instanceof Escola
            && ($this->user()?->can('update', $escola) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // nucleo_id e codigo são imutáveis no update (não aceitos).
        return $this->fieldRules();
    }

    protected function prepareForValidation(): void
    {
        $this->normalize();
    }

    /** @return array<string, mixed> */
    public function mappedAttributes(): array
    {
        return $this->mappedEscolaAttributes();
    }
}
