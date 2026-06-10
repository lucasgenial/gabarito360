<?php

namespace Tests\Feature\Auth;

use App\Models\DispositivoMobile;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_login_registers_device_and_associates_token(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile@example.test',
            'password' => 'senha-correta',
        ]);
        $identifier = (string) Str::uuid();

        $response = $this->postJson('/api/v1/auth/login', $this->mobileCredentials(
            email: $user->email,
            password: 'senha-correta',
            identifier: $identifier,
        ));

        $response
            ->assertOk()
            ->assertJsonPath('data.dispositivo.identificador', $identifier)
            ->assertJsonPath('data.dispositivo.plataforma', 'android')
            ->assertJsonPath('data.dispositivo.versao_app', '1.0.0')
            ->assertJsonMissingPath('data.dispositivo.modelo_dispositivo')
            ->assertJsonMissingPath('data.dispositivo.versao_sistema');

        $device = DispositivoMobile::query()->sole();
        $token = PersonalAccessToken::query()->sole();

        $this->assertSame($user->id, $device->usuario_id);
        $this->assertSame($device->id, $token->dispositivo_mobile_id);
        $this->assertSame(['mobile'], $token->abilities);
        $this->assertNotNull($token->expires_at);
    }

    public function test_new_mobile_login_rotates_token_for_the_same_device(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile@example.test',
            'password' => 'senha-correta',
        ]);
        $identifier = (string) Str::uuid();
        $credentials = $this->mobileCredentials($user->email, 'senha-correta', $identifier);

        $firstToken = $this->postJson('/api/v1/auth/login', $credentials)->json('data.token');
        $secondToken = $this->postJson('/api/v1/auth/login', $credentials)->json('data.token');

        $this->assertNotSame($firstToken, $secondToken);
        $this->assertDatabaseCount('dispositivos_mobile', 1);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertNull(PersonalAccessToken::findToken($firstToken));
        $this->assertNotNull(PersonalAccessToken::findToken($secondToken));
    }

    public function test_revoked_device_loses_access_and_cannot_authenticate_again(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile@example.test',
            'password' => 'senha-correta',
        ]);
        $credentials = $this->mobileCredentials(
            $user->email,
            'senha-correta',
            (string) Str::uuid(),
        );
        $token = $this->postJson('/api/v1/auth/login', $credentials)->json('data.token');

        $device = DispositivoMobile::query()->sole();
        $device->forceFill(['revogado_at' => now()])->save();

        $this->assertDatabaseCount('personal_access_tokens', 1);

        Auth::forgetGuards();
        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');

        $this->postJson('/api/v1/auth/login', $credentials)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'DEVICE_REVOKED');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_existing_mobile_token_requires_supported_version_but_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile@example.test',
            'password' => 'senha-correta',
        ]);
        $token = $this->postJson('/api/v1/auth/login', $this->mobileCredentials(
            $user->email,
            'senha-correta',
            (string) Str::uuid(),
        ))->json('data.token');

        config(['gabarito360.mobile.minimum_app_version' => '2.0.0']);

        Auth::forgetGuards();
        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertStatus(426)
            ->assertJsonPath('error.code', 'APP_VERSION_UNSUPPORTED')
            ->assertJsonPath('error.details.minimum_version', '2.0.0');

        Auth::forgetGuards();
        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    public function test_unsupported_mobile_app_version_is_rejected(): void
    {
        config(['gabarito360.mobile.minimum_app_version' => '2.0.0']);
        $user = User::factory()->create([
            'email' => 'mobile@example.test',
            'password' => 'senha-correta',
        ]);

        $this->postJson('/api/v1/auth/login', $this->mobileCredentials(
            $user->email,
            'senha-correta',
            (string) Str::uuid(),
            version: '1.9.9',
        ))
            ->assertStatus(426)
            ->assertJsonPath('error.code', 'APP_VERSION_UNSUPPORTED')
            ->assertJsonPath('error.details.minimum_version', '2.0.0');

        $this->assertDatabaseCount('dispositivos_mobile', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_mobile_identifier_must_be_an_app_generated_uuid(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile@example.test',
            'password' => 'senha-correta',
        ]);

        $this->postJson('/api/v1/auth/login', $this->mobileCredentials(
            $user->email,
            'senha-correta',
            'IMEI-NAO-DEVE-SER-COLETADO',
        ))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'dispositivo.identificador',
                    ],
                ],
            ]);

        $this->assertDatabaseCount('dispositivos_mobile', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_abusive_login_receives_too_many_requests_response(): void
    {
        config(['gabarito360.auth.login_max_attempts_per_minute' => 2]);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'inexistente@example.test',
                'password' => 'senha-incorreta',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inexistente@example.test',
            'password' => 'senha-incorreta',
        ])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
    }

    /**
     * @return array<string, mixed>
     */
    private function mobileCredentials(
        string $email,
        string $password,
        string $identifier,
        string $version = '1.0.0',
    ): array {
        return [
            'email' => $email,
            'password' => $password,
            'dispositivo' => [
                'identificador' => $identifier,
                'plataforma' => 'android',
                'modelo_dispositivo' => 'Modelo homologado',
                'versao_sistema' => 'Android 14',
                'versao_app' => $version,
            ],
        ];
    }
}
