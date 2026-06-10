<?php

namespace App\Services\Audit;

use App\Models\Auditoria;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Stringable;

class AuditService
{
    /** @var list<string> */
    private const SENSITIVE_KEY_FRAGMENTS = [
        'authorization',
        'cookie',
        'documento',
        'email',
        'password',
        'secret',
        'senha',
        'telefone',
        'token',
    ];

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        AuditAction $action,
        string $entityType,
        ?string $entityId = null,
        ?string $actorUserId = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
        ?string $nucleoId = null,
        ?string $escolaId = null,
    ): Auditoria {
        $request = $this->request();

        return Auditoria::query()->create([
            'request_id' => $this->requestId($request),
            'usuario_id' => $actorUserId ?? $this->authenticatedUserId(),
            'nucleo_id' => $nucleoId,
            'escola_id' => $escolaId,
            'acao' => $action->value,
            'entidade_tipo' => $entityType,
            'entidade_id' => $entityId,
            'dados_anteriores' => $this->sanitize($before),
            'dados_novos' => $this->sanitize($after),
            'metadados' => $this->sanitize($metadata),
            'ip' => $request?->ip(),
            'user_agent' => $this->userAgent($request),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function sanitize(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                continue;
            }

            $sanitized[$key] = $this->sanitizeValue($value);
        }

        return $sanitized;
    }

    private function request(): ?Request
    {
        return app()->bound('request') ? request() : null;
    }

    private function requestId(?Request $request): ?string
    {
        $requestId = $request?->attributes->get('request_id');

        return is_string($requestId) && Str::isUuid($requestId) ? $requestId : null;
    }

    private function authenticatedUserId(): ?string
    {
        $userId = Auth::id();

        return is_string($userId) ? $userId : null;
    }

    private function userAgent(?Request $request): ?string
    {
        $userAgent = $request?->userAgent();

        return is_string($userAgent) ? Str::limit($userAgent, 1000, '') : null;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = Str::lower($key);

        return collect(self::SENSITIVE_KEY_FRAGMENTS)
            ->contains(fn (string $fragment): bool => str_contains($normalized, $fragment));
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitize($value);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_string($value) && str_starts_with(Str::lower(trim($value)), 'bearer ')) {
            return '[REDACTED]';
        }

        return is_scalar($value) || $value === null ? $value : '[UNSUPPORTED]';
    }
}
