<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\Auditoria;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use LogicException;
use Tests\TestCase;

class AccessAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_are_audited_without_secrets_or_unnecessary_personal_data(): void
    {
        $user = User::factory()->create([
            'email' => 'audit@example.test',
            'password' => 'senha-super-secreta',
            'documento' => 'documento-nao-deve-aparecer',
            'telefone' => 'telefone-nao-deve-aparecer',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'senha-super-secreta',
            'token_name' => 'token-nao-deve-aparecer',
        ])->assertOk();

        $loginAudit = Auditoria::query()
            ->where('acao', AuditAction::LOGIN_SUCCEEDED->value)
            ->sole();

        $this->assertSame($user->id, $loginAudit->usuario_id);
        $this->assertSame($user->id, $loginAudit->entidade_id);
        $this->assertSame($login->json('meta.request_id'), $loginAudit->request_id);
        $this->assertStringNotContainsString(
            strtolower($login->json('data.token')),
            strtolower((string) json_encode($loginAudit->toArray())),
        );
        $this->assertAuditDoesNotContainSecrets($loginAudit);

        Auth::forgetGuards();
        $this->withToken($login->json('data.token'))
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $logoutAudit = Auditoria::query()
            ->where('acao', AuditAction::LOGOUT->value)
            ->sole();

        $this->assertSame($user->id, $logoutAudit->usuario_id);
        $this->assertAuditDoesNotContainSecrets($logoutAudit);
    }

    public function test_failed_blocked_and_rate_limited_logins_are_audited(): void
    {
        config(['gabarito360.auth.login_max_attempts_per_minute' => 1]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inexistente@example.test',
            'password' => 'senha-incorreta',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inexistente@example.test',
            'password' => 'senha-incorreta',
        ])->assertTooManyRequests();

        $inactive = User::factory()->create([
            'email' => 'inativo@example.test',
            'password' => 'senha-correta',
            'status' => UserStatus::INACTIVE,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $inactive->email,
            'password' => 'senha-correta',
        ])->assertUnauthorized();

        $this->assertDatabaseHas('auditorias', ['acao' => AuditAction::LOGIN_FAILED->value]);
        $this->assertDatabaseHas('auditorias', ['acao' => AuditAction::LOGIN_RATE_LIMITED->value]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::LOGIN_BLOCKED_USER->value,
            'usuario_id' => null,
            'entidade_id' => $inactive->id,
        ]);

        Auditoria::query()->each(fn (Auditoria $audit) => $this->assertAuditDoesNotContainSecrets($audit));
    }

    public function test_user_blocks_and_profile_changes_are_audited(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $profile = Perfil::factory()->create();
        $token = $target->createToken('audit-block-test')->plainTextToken;

        $link = UsuarioPerfil::factory()->create([
            'usuario_id' => $target->id,
            'perfil_id' => $profile->id,
            'concedido_por' => $actor->id,
        ]);

        $link->update(['fim_at' => now()]);
        $target->update(['status' => UserStatus::BLOCKED]);

        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::PROFILE_GRANTED->value,
            'usuario_id' => $actor->id,
            'entidade_id' => $link->id,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::PROFILE_REVOKED->value,
            'entidade_id' => $link->id,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::USER_STATUS_CHANGED->value,
            'entidade_id' => $target->id,
        ]);

        Auth::forgetGuards();
        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();

        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::ACCESS_BLOCKED_USER->value,
            'usuario_id' => $target->id,
            'entidade_id' => $target->id,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_audit_service_sanitizes_nested_secrets_and_records_are_immutable(): void
    {
        $audit = app(AuditService::class)->record(
            action: AuditAction::LOGIN_FAILED,
            entityType: 'autenticacao',
            metadata: [
                'password' => 'segredo',
                'nested' => [
                    'access_token' => 'segredo',
                    'header' => 'Bearer segredo',
                    'motivo' => 'credenciais_invalidas',
                ],
            ],
        );

        $this->assertSame([
            'nested' => [
                'header' => '[REDACTED]',
                'motivo' => 'credenciais_invalidas',
            ],
        ], $audit->metadados);
        $this->assertAuditDoesNotContainSecrets($audit);

        $this->expectException(LogicException::class);

        $audit->update(['acao' => AuditAction::LOGIN_SUCCEEDED->value]);
    }

    private function assertAuditDoesNotContainSecrets(Auditoria $audit): void
    {
        $serialized = strtolower((string) json_encode($audit->toArray()));

        $this->assertStringNotContainsString('senha-super-secreta', $serialized);
        $this->assertStringNotContainsString('senha-incorreta', $serialized);
        $this->assertStringNotContainsString('token-nao-deve-aparecer', $serialized);
        $this->assertStringNotContainsString('documento-nao-deve-aparecer', $serialized);
        $this->assertStringNotContainsString('telefone-nao-deve-aparecer', $serialized);
        $this->assertStringNotContainsString('audit@example.test', $serialized);
    }
}
