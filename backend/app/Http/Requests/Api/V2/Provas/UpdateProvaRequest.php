<?php

namespace App\Http\Requests\Api\V2\Provas;

use App\Http\Requests\Api\V2\Provas\Concerns\ResolvesProvaCatalog;
use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProvaRequest extends FormRequest
{
    use ResolvesProvaCatalog;

    public function authorize(): bool
    {
        $prova = $this->route('prova');

        return $prova instanceof Prova
            && ($this->user()?->can('update', $prova) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'string', 'max:180'],
            'disciplina' => ['sometimes', 'string', 'max:120'],
            'serie' => ['sometimes', 'nullable', 'string', 'max:60'],
            'num_questoes' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'padrao' => ['sometimes', 'array'],
            'padrao.alternativas' => ['sometimes', Rule::in([3, 4, 5])],
            'padrao.nota_maxima' => ['sometimes', 'numeric', 'min:0'],
            'padrao.pontuacao' => ['sometimes', Rule::in(['iguais', 'personalizados'])],
            'padrao.anular_se_todas_marcadas' => ['sometimes', 'boolean'],
            'padrao.gerar_cartao_pdf' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function mappedAttributes(): array
    {
        $attributes = [];

        if ($this->has('titulo')) {
            $attributes['titulo'] = trim((string) $this->input('titulo'));
        }

        if ($this->has('disciplina')) {
            $attributes['disciplina_id'] = $this->resolveDisciplinaId((string) $this->input('disciplina'));
        }

        if ($this->has('serie')) {
            $attributes['serie_ano_id'] = $this->resolveSerieAnoId($this->input('serie'));
        }

        if ($this->has('num_questoes')) {
            $attributes['quantidade_questoes'] = (int) $this->input('num_questoes');
        }

        if ($this->has('padrao')) {
            $padrao = $this->mapPadrao();
            $attributes['quantidade_alternativas'] = $padrao['quantidade_alternativas'];
            $attributes['alternativas'] = $padrao['alternativas'];
            $attributes['valor_total'] = $padrao['valor_total'];
            $attributes['padrao'] = $padrao['padrao'];
        }

        return $attributes;
    }
}
