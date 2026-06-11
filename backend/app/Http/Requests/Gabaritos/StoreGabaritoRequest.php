<?php

namespace App\Http\Requests\Gabaritos;

use App\Http\Requests\Provas\Concerns\ResolvesScopedProva;
use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;

class StoreGabaritoRequest extends FormRequest
{
    use ResolvesScopedProva;

    public function authorize(): bool
    {
        $exam = $this->resolveProva();

        return $exam instanceof Prova
            && ($this->user()?->can('update', $exam) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'versao' => ['prohibited'],
            'status' => ['prohibited'],
            'justificativa' => ['prohibited'],
            'criado_por' => ['prohibited'],
            'publicado_por' => ['prohibited'],
            'publicado_at' => ['prohibited'],
        ];
    }
}
