<?php

namespace Tests\Feature\Web;

use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_active_authorized_user_can_log_in_and_log_out_of_panel(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->get('/admin')
            ->assertRedirect('/login');

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')
            ->assertOk()
            ->assertSee('Gestao organizacional')
            ->assertSee('Nucleos')
            ->assertSee('Escolas')
            ->assertSee('Usuarios');

        $this->post('/admin/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_administrator_can_operate_nuclei_schools_users_and_profiles(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $this->actingAs($admin);

        $this->post('/admin/nucleos', [
            'codigo' => ' nuc-centro ',
            'nome' => ' Nucleo Centro ',
            'municipio' => ' Cidade Teste ',
            'estado' => 'sp',
        ])->assertRedirect();

        $nucleo = Nucleo::query()->where('codigo', 'NUC-CENTRO')->firstOrFail();

        $this->patch('/admin/nucleos/'.$nucleo->id, [
            'nome' => 'Nucleo Centro Atualizado',
            'municipio' => $nucleo->municipio,
            'estado' => $nucleo->estado,
            'email' => null,
            'telefone' => null,
        ])->assertRedirect();

        $this->post('/admin/escolas', [
            'nucleo_id' => $nucleo->id,
            'codigo' => ' escola-01 ',
            'nome' => ' Escola Centro ',
            'municipio' => ' Cidade Teste ',
            'estado' => 'sp',
        ])->assertRedirect();

        $escola = Escola::query()->where('codigo', 'ESCOLA-01')->firstOrFail();

        $this->patch('/admin/escolas/'.$escola->id, [
            'codigo' => $escola->codigo,
            'nome' => 'Escola Centro Atualizada',
            'municipio' => $escola->municipio,
            'estado' => $escola->estado,
            'email' => null,
            'telefone' => null,
        ])->assertRedirect();

        $this->post('/admin/usuarios', [
            'nome' => 'Responsavel Escola',
            'email' => 'responsavel@example.test',
            'password' => 'SenhaSegura123',
            'password_confirmation' => 'SenhaSegura123',
            'perfil_id' => $this->profile(UserRole::SCHOOL_MANAGER)->id,
            'escola_id' => $escola->id,
        ])->assertRedirect();

        $target = User::query()->where('email', 'responsavel@example.test')->firstOrFail();

        $this->patch('/admin/usuarios/'.$target->id, [
            'nome' => 'Responsavel Atualizada',
            'email' => 'responsavel.atualizada@example.test',
        ])->assertRedirect();

        $this->post('/admin/usuarios/'.$target->id.'/perfis', [
            'perfil_id' => $this->profile(UserRole::APPLICATOR)->id,
            'escola_id' => $escola->id,
        ])->assertRedirect();

        $applicatorLink = UsuarioPerfil::query()
            ->where('usuario_id', $target->id)
            ->where('perfil_id', $this->profile(UserRole::APPLICATOR)->id)
            ->firstOrFail();

        $this->delete('/admin/usuarios/'.$target->id.'/perfis/'.$applicatorLink->id)
            ->assertRedirect();
        $this->assertNotNull($applicatorLink->fresh()->fim_at);

        $this->post('/admin/usuarios/'.$target->id.'/inativar', ['confirmacao' => 'on'])
            ->assertRedirect('/admin/usuarios');
        $this->assertSame(UserStatus::INACTIVE, $target->fresh()->status);

        $this->delete('/admin/escolas/'.$escola->id, ['confirmacao' => 'on'])
            ->assertRedirect('/admin/escolas');
        $this->assertSame(StatusEnum::INACTIVE, $escola->fresh()->status);

        $this->delete('/admin/nucleos/'.$nucleo->id, ['confirmacao' => 'on'])
            ->assertRedirect('/admin/nucleos');
        $this->assertSame(StatusEnum::INACTIVE, $nucleo->fresh()->status);
    }

    public function test_education_manager_panel_is_limited_to_authorized_scope(): void
    {
        $ownNucleo = Nucleo::factory()->create(['nome' => 'Nucleo autorizado']);
        $foreignNucleo = Nucleo::factory()->create(['nome' => 'Nucleo restrito']);
        $ownSchool = Escola::factory()->create([
            'nucleo_id' => $ownNucleo->id,
            'nome' => 'Escola autorizada',
        ]);
        $foreignSchool = Escola::factory()->create([
            'nucleo_id' => $foreignNucleo->id,
            'nome' => 'Escola restrita',
        ]);
        $manager = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleo: $ownNucleo);
        $this->actingAs($manager);

        $this->get('/admin/nucleos')->assertForbidden();

        $this->get('/admin/escolas')
            ->assertOk()
            ->assertSee($ownSchool->nome)
            ->assertDontSee($foreignSchool->nome);

        $this->patch('/admin/escolas/'.$foreignSchool->id, [
            'nome' => 'Tentativa fora do escopo',
        ])->assertForbidden();

        $this->post('/admin/escolas', [
            'nucleo_id' => $foreignNucleo->id,
            'codigo' => 'FORA-ESCOPO',
            'nome' => 'Fora do escopo',
            'municipio' => 'Cidade',
            'estado' => 'SP',
        ])->assertForbidden();

        $this->assertNotSame('Tentativa fora do escopo', $foreignSchool->fresh()->nome);
    }

    public function test_user_list_minimizes_personal_data_and_blocks_cross_scope_access(): void
    {
        $ownSchool = Escola::factory()->create();
        $foreignSchool = Escola::factory()->create();
        $manager = $this->userWithRole(UserRole::SCHOOL_MANAGER, escola: $ownSchool);
        $ownTarget = $this->userWithRole(UserRole::TEACHER, escola: $ownSchool, attributes: [
            'email' => 'proprio@example.test',
            'documento' => 'DOCUMENTO-NAO-EXIBIR',
            'telefone' => 'TELEFONE-NAO-EXIBIR',
        ]);
        $foreignTarget = $this->userWithRole(UserRole::TEACHER, escola: $foreignSchool, attributes: [
            'email' => 'restrito@example.test',
        ]);
        $this->actingAs($manager);

        $this->get('/admin/usuarios')
            ->assertOk()
            ->assertSee($ownTarget->email)
            ->assertDontSee('DOCUMENTO-NAO-EXIBIR')
            ->assertDontSee('TELEFONE-NAO-EXIBIR')
            ->assertDontSee($foreignTarget->email);

        $this->get('/admin/usuarios/'.$foreignTarget->id.'/editar')
            ->assertForbidden();

        $this->patch('/admin/usuarios/'.$foreignTarget->id, [
            'nome' => 'Tentativa fora do escopo',
        ])->assertForbidden();

        $this->assertNotSame('Tentativa fora do escopo', $foreignTarget->fresh()->nome);
    }

    private function userWithRole(
        UserRole $role,
        ?Nucleo $nucleo = null,
        ?Escola $escola = null,
        array $attributes = [],
    ): User {
        $user = User::factory()->create($attributes);

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $this->profile($role)->id,
            'nucleo_id' => $role === UserRole::EDUCATION_MANAGER ? $nucleo?->id : null,
            'escola_id' => in_array($role, [UserRole::SCHOOL_MANAGER, UserRole::TEACHER], strict: true)
                ? $escola?->id
                : null,
            'inicio_at' => now()->subMinute(),
        ]);

        return $user;
    }

    private function profile(UserRole $role): Perfil
    {
        return Perfil::query()->where('codigo', $role->value)->firstOrFail();
    }
}
