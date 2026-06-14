<?php

namespace App\Http\Requests\Api\V2\Turmas;

use App\Models\Escola;
use App\Models\Turma;
use Illuminate\Support\Str;

class StoreTurmaRequest extends TurmaApiRequest
{
    public function authorize(): bool
    {
        $escolaId = $this->input('escola_id');

        if (is_string($escolaId) && Str::isUuid($escolaId)) {
            $escola = Escola::query()->find($escolaId);

            return $escola instanceof Escola
                && ($this->user()?->can('create', [Turma::class, $escola]) ?? false);
        }

        return $this->user()?->can('viewAny', Turma::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->fieldRules();
    }

    /** @return array<string, mixed> */
    public function mappedAttributes(): array
    {
        return $this->mappedTurmaAttributes();
    }
}
