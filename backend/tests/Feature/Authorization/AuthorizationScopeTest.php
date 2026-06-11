<?php

namespace Tests\Feature\Authorization;

use App\Enums\PermissionCode;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Authorization\AuthorizationContext;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorizationScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_global_profile_respects_permissions_without_implicit_operational_access(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $profile = Perfil::query()->where('codigo', UserRole::ADMINISTRATOR->value)->firstOrFail();
        $operationalPermission = Permissao::query()
            ->where('codigo', PermissionCode::RUN_APPLICATIONS->value)
            ->firstOrFail();
        $profile->permissoes()->attach($operationalPermission);
        $schoolContext = AuthorizationContext::school((string) Str::uuid(), (string) Str::uuid());

        $this->assertTrue($this->allows($admin, PermissionCode::MANAGE_CLASSES_STUDENTS, $schoolContext));
        $this->assertFalse($this->allows(
            $admin,
            PermissionCode::RUN_APPLICATIONS,
            AuthorizationContext::operational(explicitlyLinked: true),
        ));
    }

    public function test_education_center_profile_is_isolated_from_other_centers(): void
    {
        $authorizedCenter = Nucleo::factory()->create()->id;
        $manager = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $authorizedCenter);

        $this->assertTrue($this->allows(
            $manager,
            PermissionCode::MANAGE_SCHOOLS,
            AuthorizationContext::educationCenter($authorizedCenter),
        ));
        $this->assertTrue($this->allows(
            $manager,
            PermissionCode::MANAGE_CLASSES_STUDENTS,
            AuthorizationContext::school($authorizedCenter, (string) Str::uuid()),
        ));
        $this->assertFalse($this->allows(
            $manager,
            PermissionCode::MANAGE_SCHOOLS,
            AuthorizationContext::educationCenter((string) Str::uuid()),
        ));
        $this->assertFalse($this->allows($manager, PermissionCode::MANAGE_SCHOOLS, AuthorizationContext::global()));
    }

    public function test_school_profile_is_isolated_from_other_schools(): void
    {
        $school = Escola::factory()->create()->id;
        $manager = $this->userWithRole(UserRole::SCHOOL_MANAGER, escolaId: $school);

        $this->assertTrue($this->allows(
            $manager,
            PermissionCode::MANAGE_CLASSES_STUDENTS,
            AuthorizationContext::school((string) Str::uuid(), $school),
        ));
        $this->assertFalse($this->allows(
            $manager,
            PermissionCode::MANAGE_CLASSES_STUDENTS,
            AuthorizationContext::school((string) Str::uuid(), (string) Str::uuid()),
        ));
    }

    public function test_operational_profile_requires_explicit_trusted_link(): void
    {
        $teacher = $this->userWithRole(UserRole::TEACHER);

        $this->assertFalse($this->allows(
            $teacher,
            PermissionCode::RUN_APPLICATIONS,
            AuthorizationContext::operational(explicitlyLinked: false),
        ));
        $this->assertTrue($this->allows(
            $teacher,
            PermissionCode::RUN_APPLICATIONS,
            AuthorizationContext::operational(explicitlyLinked: true),
        ));
        $this->assertFalse($this->allows(
            $teacher,
            PermissionCode::MANAGE_CLASSES_STUDENTS,
            AuthorizationContext::operational(explicitlyLinked: true),
        ));
    }

    public function test_operational_profile_scoped_to_school_requires_matching_trusted_context(): void
    {
        $school = Escola::factory()->create();
        $teacher = $this->userWithRole(UserRole::TEACHER, escolaId: $school->id);

        $this->assertTrue($this->allows(
            $teacher,
            PermissionCode::RUN_APPLICATIONS,
            AuthorizationContext::operational(
                explicitlyLinked: true,
                nucleoId: $school->nucleo_id,
                escolaId: $school->id,
            ),
        ));
        $this->assertFalse($this->allows(
            $teacher,
            PermissionCode::RUN_APPLICATIONS,
            AuthorizationContext::operational(
                explicitlyLinked: true,
                nucleoId: $school->nucleo_id,
                escolaId: (string) Str::uuid(),
            ),
        ));
    }

    public function test_viewer_remains_read_only_even_if_mutation_is_attached_by_mistake(): void
    {
        $viewer = $this->userWithRole(UserRole::VIEWER);
        $profile = Perfil::query()->where('codigo', UserRole::VIEWER->value)->firstOrFail();
        $mutation = Permissao::query()
            ->where('codigo', PermissionCode::MANAGE_CLASSES_STUDENTS->value)
            ->firstOrFail();

        $profile->permissoes()->attach($mutation);

        $context = AuthorizationContext::operational(explicitlyLinked: true);

        $this->assertTrue($this->allows($viewer, PermissionCode::VIEW_CLASSES_STUDENTS, $context));
        $this->assertFalse($this->allows($viewer, PermissionCode::MANAGE_CLASSES_STUDENTS, $context));
    }

    public function test_technical_support_requires_audited_diagnostic_context_and_cannot_mutate(): void
    {
        $support = $this->userWithRole(UserRole::TECHNICAL_SUPPORT);
        $profile = Perfil::query()->where('codigo', UserRole::TECHNICAL_SUPPORT->value)->firstOrFail();
        $mutation = Permissao::query()
            ->where('codigo', PermissionCode::MANAGE_CLASSES_STUDENTS->value)
            ->firstOrFail();

        $profile->permissoes()->attach($mutation);

        $this->assertFalse($this->allows(
            $support,
            PermissionCode::VIEW_APPLICATION_DASHBOARD,
            AuthorizationContext::school((string) Str::uuid(), (string) Str::uuid()),
        ));
        $this->assertFalse($this->allows(
            $support,
            PermissionCode::RUN_TECHNICAL_DIAGNOSTICS,
            AuthorizationContext::global(),
        ));
        $this->assertTrue($this->allows(
            $support,
            PermissionCode::RUN_TECHNICAL_DIAGNOSTICS,
            AuthorizationContext::diagnostic(auditReference: (string) Str::uuid()),
        ));
        $this->assertFalse($this->allows(
            $support,
            PermissionCode::MANAGE_CLASSES_STUDENTS,
            AuthorizationContext::diagnostic(auditReference: (string) Str::uuid()),
        ));
    }

    public function test_inactive_users_expired_links_and_missing_context_are_denied(): void
    {
        $inactiveUser = $this->userWithRole(UserRole::ADMINISTRATOR, status: UserStatus::INACTIVE);
        $expiredUser = $this->userWithRole(UserRole::ADMINISTRATOR, expired: true);
        $deletedUser = $this->userWithRole(UserRole::ADMINISTRATOR);
        $activeUserWithoutContext = $this->userWithRole(UserRole::ADMINISTRATOR);
        $deletedUser->delete();

        $this->assertFalse($this->allows(
            $inactiveUser,
            PermissionCode::MANAGE_EDUCATION_CENTERS,
            AuthorizationContext::global(),
        ));
        $this->assertFalse($this->allows(
            $expiredUser,
            PermissionCode::MANAGE_EDUCATION_CENTERS,
            AuthorizationContext::global(),
        ));
        $this->assertFalse($this->allows(
            $deletedUser,
            PermissionCode::MANAGE_EDUCATION_CENTERS,
            AuthorizationContext::global(),
        ));
        $this->assertFalse(
            Gate::forUser($activeUserWithoutContext)->allows(PermissionCode::MANAGE_EDUCATION_CENTERS->value),
        );
    }

    private function allows(
        User $user,
        PermissionCode $permission,
        AuthorizationContext $context,
    ): bool {
        return Gate::forUser($user)->allows($permission->value, $context);
    }

    private function userWithRole(
        UserRole $role,
        ?string $nucleoId = null,
        ?string $escolaId = null,
        UserStatus $status = UserStatus::ACTIVE,
        bool $expired = false,
    ): User {
        $user = User::factory()->create(['status' => $status]);
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'nucleo_id' => $nucleoId,
            'escola_id' => $escolaId,
            'inicio_at' => now()->subMinute(),
            'fim_at' => $expired ? now()->subSecond() : null,
        ]);

        return $user;
    }
}
