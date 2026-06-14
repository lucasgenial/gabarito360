<?php

namespace Tests\Feature\Api\V2\Auth;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class AuthPasswordRecoveryTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_forgot_password_sends_reset_link_for_existing_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'conta@escola.test']);

        $this->postJson('/api/v2/auth/forgot-password', ['email' => 'conta@escola.test'])
            ->assertStatus(202);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_is_neutral_for_unknown_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v2/auth/forgot-password', ['email' => 'ninguem@escola.test'])
            ->assertStatus(202);

        Notification::assertNothingSent();
    }

    public function test_reset_password_changes_credentials_and_revokes_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@escola.test',
            'password' => 'SenhaAntiga@1',
        ]);
        $user->createToken('api', ['api']);
        $token = Password::createToken($user);

        $this->postJson('/api/v2/auth/reset-password', [
            'token' => $token,
            'email' => 'reset@escola.test',
            'senha' => 'SenhaNova@12345',
        ])->assertOk();

        $this->assertTrue(Hash::check('SenhaNova@12345', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        User::factory()->create(['email' => 'reset@escola.test']);

        $this->postJson('/api/v2/auth/reset-password', [
            'token' => 'token-invalido',
            'email' => 'reset@escola.test',
            'senha' => 'SenhaNova@12345',
        ])->assertUnprocessable();
    }

    public function test_reset_password_enforces_minimum_length(): void
    {
        User::factory()->create(['email' => 'reset@escola.test']);
        $token = Password::createToken(User::query()->where('email', 'reset@escola.test')->firstOrFail());

        $this->postJson('/api/v2/auth/reset-password', [
            'token' => $token,
            'email' => 'reset@escola.test',
            'senha' => 'curta',
        ])->assertUnprocessable()->assertJsonValidationErrors(['senha']);
    }
}
