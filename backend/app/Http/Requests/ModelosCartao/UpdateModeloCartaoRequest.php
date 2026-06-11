<?php

namespace App\Http\Requests\ModelosCartao;

use App\Enums\ModeloCartaoOrigemCodigo;
use App\Enums\ModeloCartaoTipoCodigo;
use App\Models\ModeloCartao;
use App\Models\User;
use App\Services\Authorization\ModeloCartaoScope;
use App\Services\ModelosCartao\ModeloCartaoConfigurationValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateModeloCartaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $boundModel = $this->route('modelo');

        if (! $actor instanceof User || ! $boundModel instanceof ModeloCartao) {
            return false;
        }

        $model = app(ModeloCartaoScope::class)
            ->apply(ModeloCartao::query(), $actor)
            ->find($boundModel->id);

        if (! $model instanceof ModeloCartao) {
            throw (new ModelNotFoundException)->setModel(ModeloCartao::class, [$boundModel->id]);
        }

        $this->attributes->set('managed_card_model', $model);

        return $actor->can('update', $model);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nucleo_id' => ['prohibited'],
            'nome' => ['prohibited'],
            'versao' => ['prohibited'],
            'quantidade_questoes' => ['sometimes', 'required', 'integer', 'min:1', 'max:500'],
            'quantidade_alternativas' => ['sometimes', 'required', 'integer', 'min:2', 'max:10'],
            'alternativas' => ['sometimes', 'required', 'array', 'min:2', 'max:10'],
            'alternativas.*' => ['required', 'string', 'size:1', 'regex:/^[A-Z0-9]$/'],
            'tipo_codigo' => ['sometimes', 'required', Rule::enum(ModeloCartaoTipoCodigo::class)],
            'origem_codigo' => ['sometimes', 'required', Rule::enum(ModeloCartaoOrigemCodigo::class)],
            'configuracao_omr' => ['sometimes', 'required', 'array'],
            'artefato_checksum_sha256' => ['sometimes', 'nullable', 'string', 'size:64', 'regex:/^[0-9a-f]{64}$/'],
            'status' => ['prohibited'],
            'criado_por' => ['prohibited'],
            'homologado_por' => ['prohibited'],
            'homologado_at' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->has('artefato_checksum_sha256')) {
            $checksum = trim((string) $this->input('artefato_checksum_sha256'));
            $normalized['artefato_checksum_sha256'] = $checksum === '' ? null : Str::lower($checksum);
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
                'quantidade_questoes',
                'quantidade_alternativas',
                'alternativas',
                'tipo_codigo',
                'origem_codigo',
                'configuracao_omr',
                'artefato_checksum_sha256',
            ];

            if (! collect($editable)->contains(fn (string $field): bool => $this->exists($field))) {
                $validator->errors()->add('payload', 'Informe ao menos um campo editavel.');

                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach (app(ModeloCartaoConfigurationValidator::class)->errors($this->candidate()) as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }

    /** @return array<string, mixed> */
    private function candidate(): array
    {
        /** @var ModeloCartao $model */
        $model = $this->cardModel();
        $candidate = [
            'quantidade_questoes' => $model->quantidade_questoes,
            'quantidade_alternativas' => $model->quantidade_alternativas,
            'alternativas' => $model->alternativas,
            'tipo_codigo' => $model->tipo_codigo->value,
            'origem_codigo' => $model->origem_codigo->value,
            'configuracao_omr' => $model->configuracao_omr,
            'artefato_checksum_sha256' => $model->artefato_checksum_sha256,
        ];

        foreach (array_keys($candidate) as $field) {
            if ($this->exists($field)) {
                $candidate[$field] = $this->input($field);
            }
        }

        return $candidate;
    }

    public function cardModel(): ModeloCartao
    {
        /** @var ModeloCartao $model */
        $model = $this->attributes->get('managed_card_model');

        return $model;
    }
}
