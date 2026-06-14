<?php

namespace App\Services\Provas;

use App\Enums\ModeloCartaoStatus;
use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\GabaritoResposta;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\Prova;
use Illuminate\Validation\ValidationException;

class ProvaConfigurationValidator
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, list<string>>
     */
    public function errors(array $attributes, ?Prova $current = null): array
    {
        $errors = [];
        $educationCenterId = $attributes['nucleo_id'] ?? null;
        $schoolId = $attributes['escola_id'] ?? null;
        $alternatives = $attributes['alternativas'] ?? null;

        if (($educationCenterId === null) === ($schoolId === null)) {
            $this->add($errors, 'proprietario', 'Informe exatamente um proprietario: nucleo_id ou escola_id.');

            return $errors;
        }

        $ownerEducationCenterId = null;

        if (is_string($educationCenterId)) {
            $educationCenter = Nucleo::query()->find($educationCenterId);

            if (! $educationCenter instanceof Nucleo || $educationCenter->trashed() || $educationCenter->status !== StatusEnum::ACTIVE) {
                $this->add($errors, 'nucleo_id', 'O nucleo proprietario deve estar ativo.');
            } else {
                $ownerEducationCenterId = $educationCenter->id;
            }
        }

        if (is_string($schoolId)) {
            $school = Escola::query()->with('nucleo')->find($schoolId);

            if (
                ! $school instanceof Escola
                || $school->trashed()
                || $school->status !== StatusEnum::ACTIVE
                || ! $school->nucleo instanceof Nucleo
                || $school->nucleo->status !== StatusEnum::ACTIVE
            ) {
                $this->add($errors, 'escola_id', 'A escola proprietaria e seu nucleo devem estar ativos.');
            } else {
                $ownerEducationCenterId = $school->nucleo_id;
            }
        }

        if (! is_array($alternatives) || ! array_is_list($alternatives)) {
            $this->add($errors, 'alternativas', 'As alternativas devem formar uma lista ordenada.');
        } else {
            if (
                count($alternatives) !== (int) ($attributes['quantidade_alternativas'] ?? 0)
                || count(array_unique($alternatives)) !== count($alternatives)
            ) {
                $this->add($errors, 'alternativas', 'As alternativas devem ser unicas e corresponder a quantidade informada.');
            }

            foreach ($alternatives as $alternative) {
                if (! is_string($alternative) || preg_match('/^[A-Z0-9]$/', $alternative) !== 1) {
                    $this->add($errors, 'alternativas', 'Cada alternativa deve possuir um unico caractere A-Z ou 0-9.');
                    break;
                }
            }
        }

        // O modelo de cartão OMR é opcional no B4 (entra no B5). Quando informado,
        // mantém-se a validação de homologação/compatibilidade.
        if (($attributes['modelo_cartao_id'] ?? null) !== null) {
            $model = ModeloCartao::query()->find($attributes['modelo_cartao_id']);

            if (! $model instanceof ModeloCartao || $model->status !== ModeloCartaoStatus::APPROVED) {
                $this->add($errors, 'modelo_cartao_id', 'A prova deve usar um modelo de cartao homologado.');
            } elseif ($ownerEducationCenterId !== null) {
                if ($model->nucleo_id !== null && $model->nucleo_id !== $ownerEducationCenterId) {
                    $this->add($errors, 'modelo_cartao_id', 'O modelo de cartao pertence a outro nucleo.');
                }

                if (
                    $model->quantidade_questoes !== (int) ($attributes['quantidade_questoes'] ?? 0)
                    || $model->quantidade_alternativas !== (int) ($attributes['quantidade_alternativas'] ?? 0)
                    || $model->alternativas !== $alternatives
                ) {
                    $this->add($errors, 'modelo_cartao_id', 'Quantidades e alternativas devem corresponder ao modelo de cartao.');
                }
            }
        }

        if (
            $current instanceof Prova
            && isset($attributes['quantidade_questoes'])
            && $current->questoes()->max('numero') > (int) $attributes['quantidade_questoes']
        ) {
            $this->add($errors, 'quantidade_questoes', 'A quantidade nao pode excluir numeros de questoes ja cadastradas.');
        }

        if (
            $current instanceof Prova
            && is_array($alternatives)
            && GabaritoResposta::query()
                ->where('prova_id', $current->id)
                ->where('anulada', false)
                ->whereNotIn('alternativa_correta', $alternatives)
                ->exists()
        ) {
            $this->add($errors, 'alternativas', 'As alternativas nao podem invalidar respostas oficiais ja registradas.');
        }

        return $errors;
    }

    /** @param array<string, mixed> $attributes */
    public function assertValid(array $attributes, ?Prova $current = null): void
    {
        $errors = $this->errors($attributes, $current);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @param array<string, list<string>> $errors */
    private function add(array &$errors, string $field, string $message): void
    {
        $errors[$field][] = $message;
    }
}
