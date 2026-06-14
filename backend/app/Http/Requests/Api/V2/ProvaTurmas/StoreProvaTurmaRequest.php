<?php

namespace App\Http\Requests\Api\V2\ProvaTurmas;

use App\Models\Prova;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProvaTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $prova = $this->route('prova');

        if (! $prova instanceof Prova) {
            return false;
        }

        $turma = $this->turma();

        if (! $turma instanceof Turma) {
            return $this->user()?->can('viewClassLinks', $prova) ?? false;
        }

        return $this->user()?->can('linkClass', [$prova, $turma]) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'turma_id' => ['required', 'uuid', Rule::exists('turmas', 'id')->whereNull('deleted_at')],
            'data_prevista' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function turma(): ?Turma
    {
        $id = $this->input('turma_id');

        return is_string($id) && Str::isUuid($id)
            ? Turma::query()->find($id)
            : null;
    }
}
