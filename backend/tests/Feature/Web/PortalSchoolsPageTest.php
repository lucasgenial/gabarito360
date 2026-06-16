<?php

namespace Tests\Feature\Web;

use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalSchoolsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_education_manager_can_create_update_and_reactivate_school_from_portal(): void
    {
        $nucleo = Nucleo::factory()->create(['nome' => 'Nucleo Centro']);
        $manager = $this->userWithLinks([
            ['role' => UserRole::EDUCATION_MANAGER, 'nucleo' => $nucleo],
        ]);

        $this->actingAs($manager);

        $this->post('/escolas', [
            'form_mode' => 'create',
            'nome' => 'Escola Portal',
            'inep' => '12345678',
            'rede' => 'estadual',
            'endereco' => 'Rua das Flores, 100',
            'cidade' => 'Brasilia',
            'uf' => 'DF',
            'telefone' => '(61) 3000-1000',
            'email' => 'portal@escola.test',
            'diretor' => 'Maria Gestora',
            'ativa' => '1',
        ])->assertRedirect('/escolas');

        $school = Escola::query()
            ->where('nucleo_id', $nucleo->id)
            ->where('nome', 'Escola Portal')
            ->firstOrFail();

        $this->assertNotNull($school->codigo);
        $this->assertSame(StatusEnum::ACTIVE, $school->status);

        $this->patch('/escolas/'.$school->id, [
            'form_mode' => 'edit',
            'school_id' => $school->id,
            'nome' => 'Escola Portal Atualizada',
            'inep' => '12345678',
            'rede' => 'municipal',
            'endereco' => 'Rua Nova, 200',
            'cidade' => 'Brasilia',
            'uf' => 'DF',
            'telefone' => '(61) 3000-2000',
            'email' => 'portal.atualizada@escola.test',
            'diretor' => 'Maria Gestora',
            'ativa' => '0',
        ])->assertRedirect('/escolas');

        $school->refresh();
        $this->assertSame('Escola Portal Atualizada', $school->nome);
        $this->assertSame(StatusEnum::INACTIVE, $school->status);

        $this->post('/escolas/'.$school->id.'/reativar')
            ->assertRedirect('/escolas');

        $this->assertSame(StatusEnum::ACTIVE, $school->fresh()->status);
    }

    public function test_school_list_shows_scope_data_and_conditional_nucleus_selector(): void
    {
        $nucleoA = Nucleo::factory()->create(['nome' => 'Nucleo Norte']);
        $nucleoB = Nucleo::factory()->create(['nome' => 'Nucleo Sul']);
        $escolaA = Escola::factory()->create(['nucleo_id' => $nucleoA->id, 'nome' => 'Escola Norte']);
        $escolaB = Escola::factory()->create(['nucleo_id' => $nucleoB->id, 'nome' => 'Escola Sul']);
        $manager = $this->userWithLinks([
            ['role' => UserRole::EDUCATION_MANAGER, 'nucleo' => $nucleoA],
            ['role' => UserRole::EDUCATION_MANAGER, 'nucleo' => $nucleoB],
        ]);

        $this->actingAs($manager);

        $this->get('/escolas')
            ->assertOk()
            ->assertSee('Escolas')
            ->assertSee($escolaA->nome)
            ->assertSee($escolaB->nome)
            ->assertSee('Nucleo responsavel')
            ->assertSee('Nova escola');
    }

    /**
     * @param  list<array{role: UserRole, nucleo?: Nucleo, escola?: Escola}>  $links
     */
    private function userWithLinks(array $links): User
    {
        $user = User::factory()->create();

        foreach ($links as $link) {
            $role = $link['role'];

            UsuarioPerfil::factory()->create([
                'usuario_id' => $user->id,
                'perfil_id' => $this->profile($role)->id,
                'nucleo_id' => $link['nucleo']->id ?? null,
                'escola_id' => $link['escola']->id ?? null,
                'inicio_at' => now()->subMinute(),
            ]);
        }

        return $user;
    }

    private function profile(UserRole $role): Perfil
    {
        return Perfil::query()->where('codigo', $role->value)->firstOrFail();
    }
}
