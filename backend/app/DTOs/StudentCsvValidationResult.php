<?php

namespace App\DTOs;

final readonly class StudentCsvValidationResult
{
    /**
     * @param  array<string, int>  $summary
     * @param  list<array{linha: int, campo: string, mensagem: string}>  $errors
     * @param  list<array{linha: int, matricula: string, codigo_interno: ?string, nome: string, numero_chamada: ?string}>  $rows
     */
    public function __construct(
        public array $summary,
        public array $errors,
        public array $rows,
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
