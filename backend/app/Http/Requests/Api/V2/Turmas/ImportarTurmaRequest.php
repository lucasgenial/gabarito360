<?php

namespace App\Http\Requests\Api\V2\Turmas;

use App\Models\Escola;
use App\Models\ImportacaoAluno;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ImportarTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turma = $this->class();

        if (! $turma instanceof Turma) {
            // turma_id inválido → deixa a validação retornar 422.
            return $this->user()?->can('viewAny', Turma::class) ?? false;
        }

        return $this->user()?->can('create', [ImportacaoAluno::class, $turma->escola]) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'turma_id' => [
                'required', 'uuid',
                Rule::exists('turmas', 'id')->where('status', 'ativo')->whereNull('deleted_at'),
            ],
            'arquivo' => [
                'required', 'file', 'extensions:csv',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel',
                'max:'.(int) config('gabarito360.imports.students.max_file_kilobytes'),
            ],
        ];
    }

    public function class(): ?Turma
    {
        $id = $this->input('turma_id');

        return is_string($id) && Str::isUuid($id)
            ? Turma::query()->with('escola')->find($id)
            : null;
    }

    public function school(): Escola
    {
        return $this->class()->escola;
    }
}
