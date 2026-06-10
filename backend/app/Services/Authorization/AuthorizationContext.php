<?php

namespace App\Services\Authorization;

use InvalidArgumentException;

/**
 * Internal authorization input built from persisted resources and trusted links.
 */
final readonly class AuthorizationContext
{
    private function __construct(
        public ?string $nucleoId = null,
        public ?string $escolaId = null,
        public bool $operationallyLinked = false,
        public bool $diagnostic = false,
        public ?string $auditReference = null,
    ) {}

    public static function global(): self
    {
        return new self;
    }

    public static function educationCenter(string $nucleoId): self
    {
        return new self(nucleoId: $nucleoId);
    }

    public static function school(string $nucleoId, string $escolaId): self
    {
        return new self(nucleoId: $nucleoId, escolaId: $escolaId);
    }

    public static function operational(
        bool $explicitlyLinked,
        ?string $nucleoId = null,
        ?string $escolaId = null,
    ): self {
        return new self(
            nucleoId: $nucleoId,
            escolaId: $escolaId,
            operationallyLinked: $explicitlyLinked,
        );
    }

    public static function diagnostic(
        string $auditReference,
        ?string $nucleoId = null,
        ?string $escolaId = null,
    ): self {
        if (trim($auditReference) === '') {
            throw new InvalidArgumentException('Diagnostic authorization requires an audit reference.');
        }

        return new self(
            nucleoId: $nucleoId,
            escolaId: $escolaId,
            diagnostic: true,
            auditReference: $auditReference,
        );
    }

    public function isAuditedDiagnostic(): bool
    {
        return $this->diagnostic && $this->auditReference !== null;
    }
}
