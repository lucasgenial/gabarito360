<?php

namespace App\Http\Requests\Provas;

use App\Http\Requests\Provas\Concerns\ResolvesScopedProva;
use App\Models\Prova;
use App\Services\Provas\ProvaConfigurationValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpdateProvaRequest extends FormRequest
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
            'nucleo_id' => ['prohibited'],
            'escola_id' => ['prohibited'],
            'modelo_cartao_id' => ['sometimes', 'required', 'uuid'],
            'codigo' => ['sometimes', 'required', 'string', 'max:60', 'regex:/^[A-Z0-9._-]+$/'],
            'titulo' => ['sometimes', 'required', 'string', 'max:180'],
            'descricao' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'tipo' => ['sometimes', 'required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/'],
            'nivel' => ['sometimes', 'nullable', 'string', 'max:80'],
            'ano_referencia' => ['sometimes', 'nullable', 'integer', 'between:2000,2100'],
            'quantidade_questoes' => ['sometimes', 'required', 'integer', 'min:1', 'max:500'],
            'quantidade_alternativas' => ['sometimes', 'required', 'integer', 'min:2', 'max:10'],
            'alternativas' => ['sometimes', 'required', 'array', 'min:2', 'max:10'],
            'alternativas.*' => ['required', 'string', 'size:1', 'regex:/^[A-Z0-9]$/'],
            'status' => ['prohibited'],
            'criado_por' => ['prohibited'],
            'publicada_at' => ['prohibited'],
            'finalizada_at' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['titulo'] as $field) {
            if ($this->has($field)) {
                $normalized[$field] = trim((string) $this->input($field));
            }
        }

        foreach (['descricao', 'nivel'] as $field) {
            if ($this->has($field)) {
                $value = trim((string) $this->input($field));
                $normalized[$field] = $value === '' ? null : $value;
            }
        }

        if ($this->has('codigo')) {
            $normalized['codigo'] = Str::upper(trim((string) $this->input('codigo')));
        }

        if ($this->has('tipo')) {
            $normalized['tipo'] = Str::lower(trim((string) $this->input('tipo')));
        }

        if (is_array($this->input('alternativas'))) {
            $normalized['alternativas'] = collect($this->input('alternativas'))
                ->map(fn (mixed $value): string => Str::upper(trim((string) $value)))
                ->values()
                ->all();
        }

        $this->merge($normalized);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $editable = [
                'modelo_cartao_id',
                'codigo',
                'titulo',
                'descricao',
                'tipo',
                'nivel',
                'ano_referencia',
                'quantidade_questoes',
                'quantidade_alternativas',
                'alternativas',
            ];

            if (! collect($editable)->contains(fn (string $field): bool => $this->exists($field))) {
                $validator->errors()->add('payload', 'Informe ao menos um campo editavel.');

                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $candidate = $this->candidate();
            $duplicate = Prova::query()
                ->where('nucleo_id', $candidate['nucleo_id'])
                ->where('escola_id', $candidate['escola_id'])
                ->whereRaw('lower(codigo) = ?', [mb_strtolower((string) $candidate['codigo'])])
                ->where('id', '<>', $this->prova()->id)
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('codigo', 'Ja existe uma prova com este codigo para o proprietario.');
            }

            foreach (app(ProvaConfigurationValidator::class)->errors($candidate, $this->prova()) as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }

    /** @return array<string, mixed> */
    private function candidate(): array
    {
        $exam = $this->prova();
        $candidate = $exam->only([
            'nucleo_id',
            'escola_id',
            'modelo_cartao_id',
            'codigo',
            'titulo',
            'descricao',
            'tipo',
            'nivel',
            'ano_referencia',
            'quantidade_questoes',
            'quantidade_alternativas',
            'alternativas',
        ]);

        foreach (array_keys($candidate) as $field) {
            if ($this->exists($field)) {
                $candidate[$field] = $this->input($field);
            }
        }

        return $candidate;
    }
}
