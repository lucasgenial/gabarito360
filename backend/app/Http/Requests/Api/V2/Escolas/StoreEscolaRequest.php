<?php

namespace App\Http\Requests\Api\V2\Escolas;

use App\Models\Escola;
use App\Models\Nucleo;
use App\Services\Authorization\SchoolScope;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEscolaRequest extends EscolaApiRequest
{
    public function authorize(): bool
    {
        $nucleoId = $this->input('nucleo_id');

        if (is_string($nucleoId) && Str::isUuid($nucleoId)) {
            $nucleo = Nucleo::query()->find($nucleoId);

            return $nucleo instanceof Nucleo
                && ($this->user()?->can('create', [Escola::class, $nucleo]) ?? false);
        }

        // Sem núcleo resolvido: deixa a validação retornar 422 (nucleo_id obrigatório).
        return $this->user()?->can('viewAny', Escola::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...$this->fieldRules(),
            'nucleo_id' => [
                'required', 'uuid',
                Rule::exists('nucleos', 'id')->where('status', 'ativo')->whereNull('deleted_at'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalize();

        $nucleoId = $this->input('nucleo_id');

        if (! is_string($nucleoId) || ! Str::isUuid($nucleoId)) {
            $derivado = $this->derivarNucleoId();

            if ($derivado !== null) {
                $this->merge(['nucleo_id' => $derivado]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function mappedAttributes(): array
    {
        return [
            ...$this->mappedEscolaAttributes(),
            'nucleo_id' => $this->input('nucleo_id'),
            'codigo' => 'ESC-'.Str::upper(Str::random(8)),
        ];
    }

    /**
     * Deriva o núcleo do escopo do ator quando há exatamente um vínculo de
     * núcleo vigente (gestor de núcleo). Admin global precisa informar.
     */
    private function derivarNucleoId(): ?string
    {
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        $nucleoIds = $user->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereNotNull('nucleo_id')
            ->distinct()
            ->pluck('nucleo_id');

        if ($nucleoIds->count() !== 1) {
            return null;
        }

        $nucleoId = (string) $nucleoIds->first();

        return app(SchoolScope::class)->canAccessEducationCenter($user, $nucleoId)
            ? $nucleoId
            : null;
    }
}
