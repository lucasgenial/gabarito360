<?php

namespace Tests\Feature\Authorization;

use App\Enums\AccessScope;
use App\Enums\PermissionCode;
use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Authorization\AuthorizationContext;
use App\Services\Authorization\ScopeResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorizationResolverTest extends TestCase
{
    public function test_organizational_scopes_are_isolated(): void
    {
        $center = (string) Str::uuid();
        $school = (string) Str::uuid();
        $user = $this->activeUser();
        $resolver = $this->resolverWithLink(
            UserRole::EDUCATION_MANAGER,
            PermissionCode::MANAGE_CLASSES_STUDENTS,
            nucleoId: $center,
        );

        $this->assertTrue($resolver->allows(
            $user,
            PermissionCode::MANAGE_CLASSES_STUDENTS,
            AuthorizationContext::school($center, $school),
        ));
        $this->assertFalse($resolver->allows(
            $user,
            PermissionCode::MANAGE_CLASSES_STUDENTS,
            AuthorizationContext::school((string) Str::uuid(), $school),
        ));
    }

    public function test_operational_scope_requires_explicit_link(): void
    {
        $user = $this->activeUser();
        $resolver = $this->resolverWithLink(UserRole::TEACHER, PermissionCode::RUN_APPLICATIONS);

        $this->assertFalse($resolver->allows(
            $user,
            PermissionCode::RUN_APPLICATIONS,
            AuthorizationContext::operational(explicitlyLinked: false),
        ));
        $this->assertTrue($resolver->allows(
            $user,
            PermissionCode::RUN_APPLICATIONS,
            AuthorizationContext::operational(explicitlyLinked: true),
        ));
    }

    public function test_administrative_profile_cannot_operate_even_with_permission_attached_by_mistake(): void
    {
        $user = $this->activeUser();
        $resolver = $this->resolverWithLink(UserRole::ADMINISTRATOR, PermissionCode::RUN_APPLICATIONS);

        $this->assertFalse($resolver->allows(
            $user,
            PermissionCode::RUN_APPLICATIONS,
            AuthorizationContext::operational(explicitlyLinked: true),
        ));
    }

    public function test_read_only_and_diagnostic_profiles_have_additional_barriers(): void
    {
        $user = $this->activeUser();
        $viewerResolver = $this->resolverWithLink(UserRole::VIEWER, PermissionCode::MANAGE_CLASSES_STUDENTS);
        $supportResolver = $this->resolverWithLink(UserRole::TECHNICAL_SUPPORT, PermissionCode::RUN_TECHNICAL_DIAGNOSTICS);

        $this->assertFalse($viewerResolver->allows(
            $user,
            PermissionCode::MANAGE_CLASSES_STUDENTS,
            AuthorizationContext::operational(explicitlyLinked: true),
        ));
        $this->assertFalse($supportResolver->allows(
            $user,
            PermissionCode::RUN_TECHNICAL_DIAGNOSTICS,
            AuthorizationContext::global(),
        ));
        $this->assertTrue($supportResolver->allows(
            $user,
            PermissionCode::RUN_TECHNICAL_DIAGNOSTICS,
            AuthorizationContext::diagnostic(auditReference: 'audit-123'),
        ));
    }

    public function test_missing_context_inactive_user_and_scope_mismatch_are_denied(): void
    {
        $user = $this->activeUser();
        $resolver = $this->resolverWithLink(UserRole::ADMINISTRATOR, PermissionCode::MANAGE_EDUCATION_CENTERS);

        $this->assertFalse($resolver->allows($user, PermissionCode::MANAGE_EDUCATION_CENTERS));

        $user->status = UserStatus::INACTIVE;

        $this->assertFalse($resolver->allows(
            $user,
            PermissionCode::MANAGE_EDUCATION_CENTERS,
            AuthorizationContext::global(),
        ));

        $user->status = UserStatus::ACTIVE;
        $mismatchedResolver = $this->resolverWithLink(
            UserRole::ADMINISTRATOR,
            PermissionCode::MANAGE_EDUCATION_CENTERS,
            profileScope: AccessScope::SCHOOL,
        );

        $this->assertFalse($mismatchedResolver->allows(
            $user,
            PermissionCode::MANAGE_EDUCATION_CENTERS,
            AuthorizationContext::global(),
        ));
    }

    private function activeUser(): User
    {
        return new User([
            'status' => UserStatus::ACTIVE,
        ]);
    }

    private function resolverWithLink(
        UserRole $role,
        PermissionCode $permission,
        ?string $nucleoId = null,
        ?string $escolaId = null,
        ?AccessScope $profileScope = null,
    ): ScopeResolver {
        $profile = new Perfil([
            'codigo' => $role->value,
            'nome' => $role->label(),
            'escopo_permitido' => $profileScope ?? $role->allowedScope(),
            'sistema' => true,
            'status' => StatusEnum::ACTIVE,
        ]);
        $profile->setRelation('permissoes', new Collection([
            new Permissao(['codigo' => $permission->value]),
        ]));

        $link = new UsuarioPerfil([
            'nucleo_id' => $nucleoId,
            'escola_id' => $escolaId,
            'inicio_at' => now()->subMinute(),
        ]);
        $link->setRelation('perfil', $profile);

        return new class(new Collection([$link])) extends ScopeResolver
        {
            /** @param Collection<int, UsuarioPerfil> $links */
            public function __construct(
                private Collection $links,
            ) {}

            protected function activeLinks(User $user): Collection
            {
                return $this->links;
            }
        };
    }
}
