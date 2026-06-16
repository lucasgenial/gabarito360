<?php

namespace Tests\Feature\Web;

use App\Enums\UserRole;
use App\Models\AplicadorTurma;
use App\Models\Escola;
use App\Models\Perfil;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalRoleExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_receives_administrative_dashboard_and_navigation(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        Escola::factory()->create();
        Turma::factory()->create();

        $this->actingAs($admin)
            ->get('/painel')
            ->assertOk()
            ->assertSee('Painel administrativo')
            ->assertSee('Gestão global de escolas')
            ->assertSee('Escolas')
            ->assertSee('Provas')
            ->assertSee('Nova prova')
            ->assertDontSee('Painel do aplicador');
    }

    public function test_applicator_receives_operational_dashboard_without_admin_actions(): void
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id, 'nome' => 'Turma operacional']);
        $applicator = $this->userWithRole(UserRole::APPLICATOR);

        AplicadorTurma::factory()->create([
            'turma_id' => $class->id,
            'usuario_id' => $applicator->id,
            'inicio_em' => now()->toDateString(),
            'fim_em' => null,
        ]);

        $this->actingAs($applicator)
            ->get('/painel')
            ->assertOk()
            ->assertSee('Painel do aplicador')
            ->assertSee('Operação vinculada')
            ->assertSee('Turma operacional')
            ->assertSee('Abrir correções')
            ->assertSee('Ver minhas turmas')
            ->assertDontSee('Painel administrativo')
            ->assertDontSee('Nova prova')
            ->assertDontSee('Gerir escolas')
            ->assertDontSee('Organizar escolas')
            ->assertDontSee('Provas')
            ->assertDontSee('Escolas');
    }

    private function userWithRole(UserRole $role): User
    {
        $user = User::factory()->create();
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'inicio_at' => now()->subMinute(),
        ]);

        return $user;
    }
}
