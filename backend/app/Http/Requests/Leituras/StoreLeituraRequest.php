<?php

namespace App\Http\Requests\Leituras;

use App\Models\Aplicacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLeituraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('confirmReading', $this->route('aplicacao')) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'operacao_id' => ['required', 'string', 'max:90'],
            'aplicacao_aluno_id' => ['required', 'uuid', 'exists:aplicacao_alunos,id'],
            'arquivo_original_id' => ['required', 'uuid', 'exists:arquivos,id'],
            'dispositivo_id' => ['nullable', 'uuid', 'exists:dispositivos_mobile,id'],
            'codigo_impresso_detectado' => ['nullable', 'string', 'max:120'],
            'codigo_sistema_proposto' => ['nullable', 'string', 'regex:/^G360-[A-Z0-9]{12}-[A-Z0-9]$/'],
            'omr_versao' => ['nullable', 'string', 'max:80'],
            'omr_configuracao_checksum' => ['nullable', 'string', 'size:64'],
            'omr_metadados' => ['nullable', 'array'],
            'confianca_geral' => ['nullable', 'numeric', 'between:0,1'],
            'requer_revisao' => ['nullable', 'boolean'],
            'alertas' => ['nullable', 'array'],
            'alertas.*' => ['string', 'max:120'],
            'respostas' => ['required', 'array', 'min:1', 'max:500'],
            'respostas.*.questao_id' => ['required', 'uuid', 'distinct', 'exists:questoes,id'],
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

            $questionIds = collect($this->input('respostas'))->pluck('questao_id');
            if ($application->prova->questoes()->whereIn('id', $questionIds)->count() !== $questionIds->count()) {
                $validator->errors()->add('respostas', 'Todas as respostas devem pertencer a prova da aplicacao.');
            }
        });
    }
}
