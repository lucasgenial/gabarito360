<?php

namespace App\Http\Requests\Api\V2\Leituras;

use App\Models\Aplicacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CaptureLeituraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('run', $this->route('aplicacao')) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'imagem' => ['required', 'file', 'image', 'max:8192'],
            'aplicacao_aluno_id' => ['required', 'uuid', 'exists:aplicacao_alunos,id'],
            'operacao_id' => ['required', 'string', 'max:90'],
            'dispositivo_id' => ['nullable', 'uuid', 'exists:dispositivos_mobile,id'],
            'codigo_impresso_detectado' => ['nullable', 'string', 'max:120'],
            'codigo_sistema_proposto' => ['nullable', 'string', 'regex:/^G360-[A-Z0-9]{12}-[A-Z0-9]$/'],
            'confianca_geral' => ['nullable', 'numeric', 'between:0,1'],
            'requer_revisao' => ['nullable', 'boolean'],
            'omr_versao' => ['nullable', 'string', 'max:80'],
            'omr_metadados' => ['nullable', 'array'],
            'alertas' => ['nullable', 'array'],
            'alertas.*' => ['string', 'max:120'],
            'respostas' => ['required', 'array', 'min:1', 'max:500'],
            'respostas.*.numero' => ['required', 'integer', 'min:1', 'distinct'],
            'respostas.*.alternativa_detectada' => ['nullable', 'string', 'max:10'],
            'respostas.*.tipo_deteccao' => ['required', Rule::in(['marcada', 'branco', 'dupla', 'ambigua'])],
            'respostas.*.confianca' => ['nullable', 'numeric', 'between:0,1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $application = $this->route('aplicacao');

            if (! $application instanceof Aplicacao || $validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $application->alunos()->whereKey($this->input('aplicacao_aluno_id'))->exists()) {
                $validator->errors()->add('aplicacao_aluno_id', 'O aluno deve pertencer ao snapshot da aplicacao.');
            }

            $numeros = collect($this->input('respostas'))->pluck('numero');
            if ($application->prova->questoes()->whereIn('numero', $numeros)->count() !== $numeros->unique()->count()) {
                $validator->errors()->add('respostas', 'Todas as respostas devem referenciar questoes da prova.');
            }
        });
    }
}
