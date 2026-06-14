<?php

namespace App\Http\Requests\Api\V2\Provas;

use App\Enums\ProvaStatus;
use App\Http\Requests\Api\V2\Provas\Concerns\ResolvesProvaCatalog;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Services\Authorization\ProvaScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProvaRequest extends FormRequest
{
    use ResolvesProvaCatalog;

    public function authorize(): bool
    {
        $user = $this->user();
        $owner = $user !== null ? $this->resolveOwner($user) : null;

        if ($owner === null) {
            // Sem dono resolvido: deixa a validação retornar 422 (proprietario).
            return $user?->can('viewAny', Prova::class) ?? false;
        }

        $scope = app(ProvaScope::class);

        if ($owner['escola_id'] !== null) {
            $escola = Escola::query()->find($owner['escola_id']);

            return $escola instanceof Escola && $scope->canCreateForSchool($user, $escola);
        }

        $nucleo = Nucleo::query()->find($owner['nucleo_id']);

        return $nucleo instanceof Nucleo && $scope->canCreateForNucleo($user, $nucleo);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:180'],
            'disciplina' => ['required', 'string', 'max:120'],
            'serie' => ['sometimes', 'nullable', 'string', 'max:60'],
            'num_questoes' => ['required', 'integer', 'min:1', 'max:500'],
            'padrao' => ['sometimes', 'array'],
            'padrao.alternativas' => ['sometimes', Rule::in([3, 4, 5])],
            'padrao.nota_maxima' => ['sometimes', 'numeric', 'min:0'],
            'padrao.pontuacao' => ['sometimes', Rule::in(['iguais', 'personalizados'])],
            'padrao.anular_se_todas_marcadas' => ['sometimes', 'boolean'],
            'padrao.gerar_cartao_pdf' => ['sometimes', 'boolean'],
            'nucleo_id' => ['sometimes', 'uuid'],
            'escola_id' => ['sometimes', 'uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user !== null && $this->resolveOwner($user) === null) {
                $validator->errors()->add('proprietario', 'Informe o nucleo ou a escola proprietaria da prova.');
            }
        });
    }

    /** @return array<string, mixed> */
    public function mappedAttributes(): array
    {
        $owner = $this->resolveOwner($this->user()) ?? ['nucleo_id' => null, 'escola_id' => null];
        $padrao = $this->mapPadrao();

        return [
            'nucleo_id' => $owner['nucleo_id'],
            'escola_id' => $owner['escola_id'],
            'disciplina_id' => $this->resolveDisciplinaId((string) $this->input('disciplina')),
            'serie_ano_id' => $this->resolveSerieAnoId($this->input('serie')),
            'modelo_cartao_id' => null,
            'codigo' => 'PROVA-'.Str::upper(Str::random(8)),
            'titulo' => trim((string) $this->input('titulo')),
            'tipo' => 'avaliacao',
            'ano_referencia' => (int) now()->year,
            'quantidade_questoes' => (int) $this->input('num_questoes'),
            'quantidade_alternativas' => $padrao['quantidade_alternativas'],
            'alternativas' => $padrao['alternativas'],
            'valor_total' => $padrao['valor_total'],
            'padrao' => $padrao['padrao'],
            'status' => ProvaStatus::DRAFT->value,
        ];
    }
}
