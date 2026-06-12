<?php

namespace Database\Seeders;

use App\Enums\PermissionCode;
use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(PermissionCode::cases())
            ->mapWithKeys(function (PermissionCode $permission): array {
                $model = Permissao::query()->updateOrCreate(
                    ['codigo' => $permission->value],
                    ['descricao' => $permission->description()],
                );

                return [$permission->value => $model];
            });

        foreach ($this->profiles() as $role => $definition) {
            $profile = Perfil::query()->updateOrCreate(
                ['codigo' => $role],
                [
                    'nome' => $definition['role']->label(),
                    'descricao' => $definition['description'],
                    'escopo_permitido' => $definition['role']->allowedScope(),
                    'sistema' => true,
                    'status' => StatusEnum::ACTIVE,
                ],
            );

            $profile->permissoes()->sync(
                collect($definition['permissions'])
                    ->map(fn (PermissionCode $permission): string => $permissions[$permission->value]->id)
                    ->all(),
            );
        }
    }

    /**
     * @return array<string, array{
     *     role: UserRole,
     *     description: string,
     *     permissions: list<PermissionCode>
     * }>
     */
    private function profiles(): array
    {
        return [
            UserRole::ADMINISTRATOR->value => [
                'role' => UserRole::ADMINISTRATOR,
                'description' => 'Administracao global e diagnostico controlado.',
                'permissions' => [
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
                    PermissionCode::VIEW_RESULTS,
                    PermissionCode::VIEW_REPORTS,
                    PermissionCode::MANAGE_SETTINGS,
                    PermissionCode::RUN_TECHNICAL_DIAGNOSTICS,
                ],
            ],
            UserRole::EDUCATION_MANAGER->value => [
                'role' => UserRole::EDUCATION_MANAGER,
                'description' => 'Gestao do nucleo e de suas escolas.',
                'permissions' => [
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
                    PermissionCode::VIEW_RESULTS,
                    PermissionCode::VIEW_REPORTS,
                ],
            ],
            UserRole::SCHOOL_MANAGER->value => [
                'role' => UserRole::SCHOOL_MANAGER,
                'description' => 'Gestao administrativa dentro da escola vinculada.',
                'permissions' => [
                    PermissionCode::MANAGE_USERS_PROFILES_LINKS,
                    PermissionCode::MANAGE_CLASSES_STUDENTS,
                    PermissionCode::IMPORT_STUDENTS,
                    PermissionCode::VIEW_CLASSES_STUDENTS,
                    PermissionCode::ASSIGN_CLASS_STAFF,
                    PermissionCode::CREATE_APPLICATIONS,
                    PermissionCode::VIEW_APPLICATION_DASHBOARD,
                    PermissionCode::VIEW_EXPORT_CLASS_REPORT,
                    PermissionCode::VIEW_RESULTS,
                    PermissionCode::VIEW_REPORTS,
                ],
            ],
            UserRole::TEACHER->value => [
                'role' => UserRole::TEACHER,
                'description' => 'Operacao vinculada de aplicacoes e consulta pedagogica.',
                'permissions' => [
                    PermissionCode::VIEW_CLASSES_STUDENTS,
                    PermissionCode::MANAGE_EXAMS_ANSWER_KEYS,
                    PermissionCode::RUN_APPLICATIONS,
                    PermissionCode::CONFIRM_READINGS,
                    PermissionCode::CORRECT_READINGS_BEFORE_CONFIRMATION,
                    PermissionCode::VIEW_APPLICATION_DASHBOARD,
                    PermissionCode::VIEW_EXPORT_CLASS_REPORT,
                    PermissionCode::VIEW_RESULTS,
                    PermissionCode::VIEW_REPORTS,
                ],
            ],
            UserRole::APPLICATOR->value => [
                'role' => UserRole::APPLICATOR,
                'description' => 'Operacao vinculada de aplicacoes e leituras.',
                'permissions' => [
                    PermissionCode::VIEW_CLASSES_STUDENTS,
                    PermissionCode::RUN_APPLICATIONS,
                    PermissionCode::CONFIRM_READINGS,
                    PermissionCode::CORRECT_READINGS_BEFORE_CONFIRMATION,
                    PermissionCode::VIEW_APPLICATION_DASHBOARD,
                ],
            ],
            UserRole::VIEWER->value => [
                'role' => UserRole::VIEWER,
                'description' => 'Consulta somente leitura no escopo concedido.',
                'permissions' => [
                    PermissionCode::VIEW_CLASSES_STUDENTS,
                    PermissionCode::VIEW_APPLICATION_DASHBOARD,
                    PermissionCode::VIEW_EXPORT_CLASS_REPORT,
                    PermissionCode::VIEW_RESULTS,
                    PermissionCode::VIEW_REPORTS,
                ],
            ],
            UserRole::TECHNICAL_SUPPORT->value => [
                'role' => UserRole::TECHNICAL_SUPPORT,
                'description' => 'Diagnostico tecnico controlado sem alteracao de negocio.',
                'permissions' => [
                    PermissionCode::VIEW_CLASSES_STUDENTS,
                    PermissionCode::VIEW_APPLICATION_DASHBOARD,
                    PermissionCode::RUN_TECHNICAL_DIAGNOSTICS,
                ],
            ],
        ];
    }
}
