<?php

namespace App\Http\Requests\Provas;

use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Models\User;
use App\Services\Authorization\ProvaScope;
use App\Services\Provas\ProvaConfigurationValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreProvaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        if (! $actor instanceof User) {
            return false;
        }

        $scope = app(ProvaScope::class);
        $educationCenterId = $this->input('nucleo_id');
        $schoolId = $this->input('escola_id');

        if (is_string($educationCenterId) && Str::isUuid($educationCenterId) && empty($schoolId)) {
            $educationCenter = Nucleo::query()->find($educationCenterId);

            return $educationCenter instanceof Nucleo
                && $scope->canCreateForNucleo($actor, $educationCenter);
        }

        if (is_string($schoolId) && Str::isUuid($schoolId) && empty($educationCenterId)) {
            $school = Escola::query()->find($schoolId);

            return $school instanceof Escola
                && $scope->canCreateForSchool($actor, $school);
        }

        return $scope->canAccessAny($actor);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nucleo_id' => ['nullable', 'uuid'],
            'escola_id' => ['nullable', 'uuid'],
            'modelo_cartao_id' => ['required', 'uuid'],
            'codigo' => ['required', 'string', 'max:60', 'regex:/^[A-Z0-9._-]+$/'],
            'titulo' => ['required', 'string', 'max:180'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'tipo' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/'],
            'nivel' => ['nullable', 'string', 'max:80'],
            'ano_referencia' => ['nullable', 'integer', 'between:2000,2100'],
            'quantidade_questoes' => ['required', 'integer', 'min:1', 'max:500'],
            'quantidade_alternativas' => ['required', 'integer', 'min:2', 'max:10'],
            'alternativas' => ['required', 'array', 'min:2', 'max:10'],
            'alternativas.*' => ['required', 'string', 'size:1', 'regex:/^[A-Z0-9]$/'],
            'status' => ['prohibited'],
            'criado_por' => ['prohibited'],
            'publicada_at' => ['prohibited'],
            'finalizada_at' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [
            'nucleo_id' => $this->nullableTrimmed('nucleo_id'),
            'escola_id' => $this->nullableTrimmed('escola_id'),
            'codigo' => Str::upper(trim((string) $this->input('codigo'))),
            'titulo' => trim((string) $this->input('titulo')),
            'descricao' => $this->nullableTrimmed('descricao'),
            'tipo' => Str::lower(trim((string) $this->input('tipo'))),
            'nivel' => $this->nullableTrimmed('nivel'),
        ];

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
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateUniqueCode($validator, $this->all());

            foreach (app(ProvaConfigurationValidator::class)->errors($this->all()) as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }

    /** @param array<string, mixed> $attributes */
    private function validateUniqueCode(Validator $validator, array $attributes): void
    {
        $duplicate = Prova::query()
            ->where('nucleo_id', $attributes['nucleo_id'])
            ->where('escola_id', $attributes['escola_id'])
            ->whereRaw('lower(codigo) = ?', [mb_strtolower((string) $attributes['codigo'])])
            ->exists();

        if ($duplicate) {
            $validator->errors()->add('codigo', 'Ja existe uma prova com este codigo para o proprietario.');
        }
    }

    private function nullableTrimmed(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? null : $value;
    }
}
