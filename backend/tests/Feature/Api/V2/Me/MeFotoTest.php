<?php

namespace Tests\Feature\Api\V2\Me;

use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class MeFotoTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('private');
        $user = $this->userWithRole();

        $response = $this->withToken($this->bearerToken($user))
            ->postJson('/api/v2/me/foto', [
                'foto' => UploadedFile::fake()->create('avatar.png', 100, 'image/png'),
            ]);

        $response->assertOk();
        $this->assertNotNull($response->json('data.foto_url'));

        $arquivoId = $user->fresh()->foto_arquivo_id;
        $this->assertNotNull($arquivoId);
        $this->assertDatabaseHas('arquivos', [
            'id' => $arquivoId,
            'proprietario_tipo' => 'usuario',
            'proprietario_id' => $user->id,
        ]);
    }

    public function test_non_image_is_rejected(): void
    {
        Storage::fake('private');
        $user = $this->userWithRole();

        $this->withToken($this->bearerToken($user))
            ->postJson('/api/v2/me/foto', [
                'foto' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto']);
    }
}
