<?php

namespace App\Http\Requests\ModelosCartao;

use App\Enums\ModeloCartaoOrigemCodigo;
use App\Enums\ModeloCartaoTipoCodigo;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\User;
use App\Services\Authorization\ModeloCartaoScope;
use App\Services\ModelosCartao\ModeloCartaoConfigurationValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreModeloCartaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        if (! $actor instanceof User) {
            return false;
        }

        $scope = app(ModeloCartaoScope::class);
        $educationCenterId = $this->input('nucleo_id');

        if ($educationCenterId === null || $educationCenterId === '') {
            return $scope->canCreate($actor, null);
        }

        if (is_string($educationCenterId) && Str::isUuid($educationCenterId)) {
            $educationCenter = Nucleo::query()->find($educationCenterId);

            return $educationCenter instanceof Nucleo
                && $scope->canCreate($actor, $educationCenter);
        }

        return $scope->canAccessAny($actor);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nucleo_id' => ['nullable', 'uuid', Rule::exists('nucleos', 'id')->where('status', 'ativo')->whereNull('deleted_at')],
            'nome' => ['required', 'string', 'max:120'],
            'versao' => ['required', 'integer', 'min:1', 'max:10000'],
            'quantidade_questoes' => ['required', 'integer', 'min:1', 'max:500'],
            'quantidade_alternativas' => ['required', 'integer', 'min:2', 'max:10'],
            'alternativas' => ['required', 'array', 'min:2', 'max:10'],
            'alternativas.*' => ['required', 'string', 'size:1', 'regex:/^[A-Z0-9]$/'],
            'tipo_codigo' => ['required', Rule::enum(ModeloCartaoTipoCodigo::class)],
            'origem_codigo' => ['required', Rule::enum(ModeloCartaoOrigemCodigo::class)],
            'configuracao_omr' => ['required', 'array'],
            'artefato_checksum_sha256' => ['nullable', 'string', 'size:64', 'regex:/^[0-9a-f]{64}$/'],
            'status' => ['prohibited'],
            'criado_por' => ['prohibited'],
            'homologado_por' => ['prohibited'],
            'homologado_at' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [
            'nucleo_id' => $this->nullableTrimmed('nucleo_id'),
            'nome' => trim((string) $this->input('nome')),
            'artefato_checksum_sha256' => $this->nullableLowercase('artefato_checksum_sha256'),
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

            $duplicate = ModeloCartao::query()
                ->where('nucleo_id', $this->input('nucleo_id'))
                ->whereRaw('lower(nome) = ?', [mb_strtolower((string) $this->input('nome'))])
                ->where('versao', $this->integer('versao'))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('versao', 'Ja existe um modelo com este nome e versao no mesmo escopo.');
            }

            foreach (app(ModeloCartaoConfigurationValidator::class)->errors($this->all()) as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }

    private function nullableTrimmed(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? null : $value;
    }

    private function nullableLowercase(string $field): ?string
    {
        $value = $this->nullableTrimmed($field);

        return $value === null ? null : Str::lower($value);
    }
}
