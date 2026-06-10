<?php

namespace Tests\Feature\Api\Nucleos;

use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NucleoManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_can_create_and_consult_normalized_nucleo(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);

        $response = $this->postJson('/api/v1/nucleos', [
            'codigo' => ' nuc-centro ',
            'nome' => ' Nucleo Centro ',
            'municipio' => ' Cidade Teste ',
            'estado' => 'sp',
            'email' => ' CONTATO@EXAMPLE.TEST ',
            'telefone' => ' (11) 3333-4444 ',
        ]);

        $nucleoId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.codigo', 'NUC-CENTRO')
            ->assertJsonPath('data.nome', 'Nucleo Centro')
            ->assertJsonPath('data.estado', 'SP')
            ->assertJsonPath('data.email', 'contato@example.test')
            ->assertJsonPath('data.status', StatusEnum::ACTIVE->value);

        $this->getJson('/api/v1/nucleos/'.$nucleoId)
            ->assertOk()
            ->assertJsonPath('data.id', $nucleoId);

        $this->assertDatabaseHas('nucleos', [
            'id' => $nucleoId,
            'codigo' => 'NUC-CENTRO',
            'status' => StatusEnum::ACTIVE->value,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::EDUCATION_CENTER_CREATED->value,
            'usuario_id' => $admin->id,
            'nucleo_id' => $nucleoId,
            'entidade_id' => $nucleoId,
        ]);
    }

    public function test_code_is_unique_without_case_sensitivity(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        Nucleo::factory()->create(['codigo' => 'NUC-UNICO']);

        $this->postJson('/api/v1/nucleos', [
            'codigo' => 'nuc-unico',
            'nome' => 'Outro nucleo',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo', 'error.details');

        $this->assertDatabaseCount('nucleos', 1);
    }

    public function test_administrator_can_filter_and_paginate_nucleos(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);

        Nucleo::factory()->create([
            'codigo' => 'NORTE-01',
            'nome' => 'Nucleo Norte',
            'status' => StatusEnum::ACTIVE,
        ]);
        Nucleo::factory()->create([
            'codigo' => 'SUL-01',
            'nome' => 'Nucleo Sul',
            'status' => StatusEnum::INACTIVE,
        ]);

        $this->getJson('/api/v1/nucleos?status=ativo&search=norte&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.codigo', 'NORTE-01')
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.pagination.per_page', 1);
    }

    public function test_administrator_can_update_nucleo_but_cannot_change_stable_code_or_status_directly(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);
        $nucleo = Nucleo::factory()->create(['codigo' => 'NUC-ESTAVEL']);

        $this->patchJson('/api/v1/nucleos/'.$nucleo->id, [
            'nome' => ' Nome atualizado ',
            'estado' => 'rj',
            'email' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.codigo', 'NUC-ESTAVEL')
            ->assertJsonPath('data.nome', 'Nome atualizado')
            ->assertJsonPath('data.estado', 'RJ')
            ->assertJsonPath('data.email', null);

        $this->patchJson('/api/v1/nucleos/'.$nucleo->id, [
            'codigo' => 'OUTRO-CODIGO',
            'status' => StatusEnum::INACTIVE->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo', 'status'], 'error.details');

        $this->patchJson('/api/v1/nucleos/'.$nucleo->id, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payload', 'error.details');

        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::EDUCATION_CENTER_UPDATED->value,
            'usuario_id' => $admin->id,
            'nucleo_id' => $nucleo->id,
        ]);
    }

    public function test_delete_inactivates_nucleo_and_preserves_it_for_historical_consultation(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);
        $nucleo = Nucleo::factory()->create();

        $this->deleteJson('/api/v1/nucleos/'.$nucleo->id)
            ->assertOk()
            ->assertJsonPath('data.status', StatusEnum::INACTIVE->value);

        $this->getJson('/api/v1/nucleos/'.$nucleo->id)
            ->assertOk()
            ->assertJsonPath('data.status', StatusEnum::INACTIVE->value);

        $this->assertDatabaseHas('nucleos', [
            'id' => $nucleo->id,
            'status' => StatusEnum::INACTIVE->value,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::EDUCATION_CENTER_INACTIVATED->value,
            'usuario_id' => $admin->id,
            'nucleo_id' => $nucleo->id,
        ]);
    }

    public function test_only_global_administrator_can_access_nucleo_management(): void
    {
        $nucleo = Nucleo::factory()->create();

        $this->getJson('/api/v1/nucleos')->assertUnauthorized();

        $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleo);

        $this->getJson('/api/v1/nucleos')->assertForbidden();
        $this->getJson('/api/v1/nucleos/'.$nucleo->id)->assertForbidden();
        $this->postJson('/api/v1/nucleos', [
            'codigo' => $nucleo->codigo,
            'nome' => 'Nao autorizado',
        ])->assertForbidden();
        $this->patchJson('/api/v1/nucleos/'.$nucleo->id, [
            'nome' => 'Nao autorizado',
        ])->assertForbidden();
        $this->deleteJson('/api/v1/nucleos/'.$nucleo->id)->assertForbidden();

        $this->assertSame(StatusEnum::ACTIVE, $nucleo->fresh()->status);
    }

    private function actingAsRole(UserRole $role, ?Nucleo $nucleo = null): User
    {
        $user = User::factory()->create();
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'nucleo_id' => $role === UserRole::EDUCATION_MANAGER ? $nucleo?->id : null,
            'inicio_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user, ['api']);

        return $user;
    }
}
