<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(array $attrs = []): Usuario
    {
        return Usuario::create(array_merge([
            'perfil'   => 'professor',
            'nome'     => 'Prof. Teste',
            'email'    => 'teste@gabarito360.dev',
            'cpf'      => '00000000099',
            'password' => bcrypt('password'),
            'ativo'    => true,
        ], $attrs));
    }

    public function test_login_com_credenciais_validas_retorna_token(): void
    {
        $this->criarUsuario();

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'teste@gabarito360.dev',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token', 'perfil', 'nome', 'email']]);
    }

    public function test_login_com_credenciais_invalidas_retorna_401(): void
    {
        $this->criarUsuario();

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'teste@gabarito360.dev',
            'password' => 'senha-errada',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Credenciais inválidas.']);
    }

    public function test_logout_invalida_o_token(): void
    {
        $usuario = $this->criarUsuario();
        $token   = $usuario->createToken('api-token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_rota_protegida_sem_token_retorna_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_rota_com_perfil_errado_retorna_403(): void
    {
        $usuario = $this->criarUsuario(['perfil' => 'aluno']);
        $token   = $usuario->createToken('api-token')->plainTextToken;

        // Rota fictícia que exige perfil admin_rede
        Route::get('/api/v1/teste-perfil', fn () => 'ok')
            ->middleware(['auth:sanctum', 'perfil:admin_rede']);

        $response = $this->withToken($token)->getJson('/api/v1/teste-perfil');

        $response->assertStatus(403);
    }
}
