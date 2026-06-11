<?php

namespace App\Http\Requests\StudentImports;

use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\ImportacaoAluno;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = Escola::query()->find($this->input('escola_id'));

        return $school instanceof Escola
            ? ($this->user()?->can('create', [ImportacaoAluno::class, $school]) ?? false)
            : $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'escola_id' => [
                'required',
                'uuid',
                Rule::exists('escolas', 'id')
                    ->where('status', StatusEnum::ACTIVE->value)
                    ->whereNull('deleted_at'),
            ],
            'turma_id' => [
                'required',
                'uuid',
                Rule::exists('turmas', 'id')
                    ->where('escola_id', $this->input('escola_id'))
                    ->where('status', StatusEnum::ACTIVE->value)
                    ->whereNull('deleted_at'),
            ],
            'arquivo' => [
                'required',
                'file',
                'extensions:csv',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel',
                'max:'.config('gabarito360.imports.students.max_file_kilobytes'),
            ],
        ];
    }

    public function school(): Escola
    {
        return Escola::query()->findOrFail($this->validated('escola_id'));
    }

    public function class(): Turma
    {
        return Turma::query()->findOrFail($this->validated('turma_id'));
    }
}
