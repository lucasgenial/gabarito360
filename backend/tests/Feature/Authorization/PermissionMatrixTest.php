<?php

namespace Tests\Feature\Authorization;

use App\Enums\PermissionCode;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Authorization\AuthorizationContext;
use App\Support\ApiResponse;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private string $nucleoId;

    private string $escolaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nucleoId = (string) Str::uuid();
        $this->escolaId = (string) Str::uuid();

        $this->seed(AccessControlSeeder::class);

        Route::post('/api/v1/_permission-matrix', function (Request $request) {
            $permission = PermissionCode::from((string) $request->input('permission'));
            $context = $this->contextFromRequest($request);

            Gate::forUser($request->user())->authorize($permission->value, $context);

            return ApiResponse::success(['authorized' => true]);
        })->middleware(['auth:sanctum', EnsureUserIsActive::class]);
    }

    public function test_all_critical_role_permission_crossings_return_expected_http_status(): void
    {
        $users = collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $this->userWithRole($role)]);

        foreach ($this->permissionMatrix() as $role => $allowedPermissions) {
            foreach (PermissionCode::cases() as $permission) {
                Sanctum::actingAs($users[$role], ['api']);

                $expectedStatus = in_array($permission, $allowedPermissions, strict: true) ? 200 : 403;

                $this->postJson('/api/v1/_permission-matrix', [
                    'permission' => $permission->value,
                    ...$this->authorizedContextPayload(UserRole::from($role), $permission),
                ])->assertStatus(
                    $expectedStatus,
                    "{$role} recebeu status inesperado para {$permission->value}.",
                );
            }
        }
    }

    public function test_authentication_and_scope_barriers_return_401_or_403(): void
    {
        $this->postJson('/api/v1/_permission-matrix', [
            'permission' => PermissionCode::MANAGE_EDUCATION_CENTERS->value,
            'contexto' => 'global',
        ])->assertUnauthorized();

        $inactive = $this->userWithRole(UserRole::ADMINISTRATOR, UserStatus::INACTIVE);
        Sanctum::actingAs($inactive, ['api']);

        $this->postJson('/api/v1/_permission-matrix', [
            'permission' => PermissionCode::MANAGE_EDUCATION_CENTERS->value,
            'contexto' => 'global',
        ])->assertUnauthorized();

        foreach ($this->mismatchedScopeCases() as [$role, $permission, $payload]) {
            Sanctum::actingAs($this->userWithRole($role), ['api']);

            $this->postJson('/api/v1/_permission-matrix', [
                'permission' => $permission->value,
                ...$payload,
            ])->assertForbidden();
        }
    }

    /**
     * @return array<string, list<PermissionCode>>
     */
    private function permissionMatrix(): array
    {
        return [
            UserRole::ADMINISTRATOR->value => [
                PermissionCode::MANAGE_EDUCATION_CENTERS,
                PermissionCode::MANAGE_SCHOOLS,
                PermissionCode::MANAGE_USERS_PROFILES_LINKS,
                PermissionCode::MANAGE_CLASSES_STUDENTS,
                PermissionCode::IMPORT_STUDENTS,
                PermissionCode::VIEW_CLASSES_STUDENTS,
                PermissionCode::ASSIGN_CLASS_STAFF,
                PermissionCode::MANAGE_EXAMS_ANSWER_KEYS,
                PermissionCode::CREATE_APPLICATIONS,
                PermissionCode::VIEW_APPLICATION_DASHBOARD,
                PermissionCode::VIEW_EXPORT_CLASS_REPORT,
                PermissionCode::RUN_TECHNICAL_DIAGNOSTICS,
            ],
            UserRole::EDUCATION_MANAGER->value => [
                PermissionCode::MANAGE_SCHOOLS,
                PermissionCode::MANAGE_USERS_PROFILES_LINKS,
                PermissionCode::MANAGE_CLASSES_STUDENTS,
                PermissionCode::IMPORT_STUDENTS,
                PermissionCode::VIEW_CLASSES_STUDENTS,
                PermissionCode::ASSIGN_CLASS_STAFF,
                PermissionCode::MANAGE_EXAMS_ANSWER_KEYS,
                PermissionCode::CREATE_APPLICATIONS,
                PermissionCode::VIEW_APPLICATION_DASHBOARD,
                PermissionCode::VIEW_EXPORT_CLASS_REPORT,
            ],
            UserRole::SCHOOL_MANAGER->value => [
                PermissionCode::MANAGE_USERS_PROFILES_LINKS,
                PermissionCode::MANAGE_CLASSES_STUDENTS,
                PermissionCode::IMPORT_STUDENTS,
                PermissionCode::VIEW_CLASSES_STUDENTS,
                PermissionCode::ASSIGN_CLASS_STAFF,
                PermissionCode::CREATE_APPLICATIONS,
                PermissionCode::VIEW_APPLICATION_DASHBOARD,
                PermissionCode::VIEW_EXPORT_CLASS_REPORT,
            ],
            UserRole::TEACHER->value => [
                PermissionCode::VIEW_CLASSES_STUDENTS,
                PermissionCode::RUN_APPLICATIONS,
                PermissionCode::CONFIRM_READINGS,
                PermissionCode::CORRECT_READINGS_BEFORE_CONFIRMATION,
                PermissionCode::VIEW_APPLICATION_DASHBOARD,
                PermissionCode::VIEW_EXPORT_CLASS_REPORT,
            ],
            UserRole::APPLICATOR->value => [
                PermissionCode::VIEW_CLASSES_STUDENTS,
                PermissionCode::RUN_APPLICATIONS,
                PermissionCode::CONFIRM_READINGS,
                PermissionCode::CORRECT_READINGS_BEFORE_CONFIRMATION,
                PermissionCode::VIEW_APPLICATION_DASHBOARD,
            ],
            UserRole::VIEWER->value => [
                PermissionCode::VIEW_CLASSES_STUDENTS,
                PermissionCode::VIEW_APPLICATION_DASHBOARD,
                PermissionCode::VIEW_EXPORT_CLASS_REPORT,
            ],
            UserRole::TECHNICAL_SUPPORT->value => [
                PermissionCode::VIEW_CLASSES_STUDENTS,
                PermissionCode::VIEW_APPLICATION_DASHBOARD,
                PermissionCode::RUN_TECHNICAL_DIAGNOSTICS,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function authorizedContextPayload(UserRole $role, PermissionCode $permission): array
    {
        if ($permission === PermissionCode::RUN_TECHNICAL_DIAGNOSTICS || $role === UserRole::TECHNICAL_SUPPORT) {
            return [
                'contexto' => 'diagnostico',
                'referencia_auditoria' => (string) Str::uuid(),
            ];
        }

        return match ($role->allowedScope()->value) {
            'global' => ['contexto' => 'global'],
            'nucleo' => [
                'contexto' => 'escola',
                'nucleo_id' => $this->nucleoId,
                'escola_id' => $this->escolaId,
            ],
            'escola' => [
                'contexto' => 'escola',
                'nucleo_id' => $this->nucleoId,
                'escola_id' => $this->escolaId,
            ],
            'operacional' => [
                'contexto' => 'operacional',
                'vinculado' => true,
            ],
        };
    }

    /**
     * @return list<array{UserRole, PermissionCode, array<string, mixed>}>
     */
    private function mismatchedScopeCases(): array
    {
        return [
            [
                UserRole::EDUCATION_MANAGER,
                PermissionCode::MANAGE_SCHOOLS,
                [
                    'contexto' => 'nucleo',
                    'nucleo_id' => (string) Str::uuid(),
                ],
            ],
            [
                UserRole::SCHOOL_MANAGER,
                PermissionCode::MANAGE_CLASSES_STUDENTS,
                [
                    'contexto' => 'escola',
                    'nucleo_id' => $this->nucleoId,
                    'escola_id' => (string) Str::uuid(),
                ],
            ],
            [
                UserRole::TEACHER,
                PermissionCode::RUN_APPLICATIONS,
                [
                    'contexto' => 'operacional',
                    'vinculado' => false,
                ],
            ],
            [
                UserRole::TECHNICAL_SUPPORT,
                PermissionCode::RUN_TECHNICAL_DIAGNOSTICS,
                ['contexto' => 'global'],
            ],
        ];
    }

    private function userWithRole(UserRole $role, UserStatus $status = UserStatus::ACTIVE): User
    {
        $user = User::factory()->create(['status' => $status]);
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'nucleo_id' => $role === UserRole::EDUCATION_MANAGER ? $this->nucleoId : null,
            'escola_id' => $role === UserRole::SCHOOL_MANAGER ? $this->escolaId : null,
            'inicio_at' => now()->subMinute(),
        ]);

        return $user;
    }

    private function contextFromRequest(Request $request): AuthorizationContext
    {
        return match ($request->input('contexto')) {
            'global' => AuthorizationContext::global(),
            'nucleo' => AuthorizationContext::educationCenter((string) $request->input('nucleo_id')),
            'escola' => AuthorizationContext::school(
                (string) $request->input('nucleo_id'),
                (string) $request->input('escola_id'),
            ),
            'operacional' => AuthorizationContext::operational(
                explicitlyLinked: (bool) $request->boolean('vinculado'),
            ),
            'diagnostico' => AuthorizationContext::diagnostic(
                auditReference: (string) $request->input('referencia_auditoria'),
            ),
        };
    }
}
