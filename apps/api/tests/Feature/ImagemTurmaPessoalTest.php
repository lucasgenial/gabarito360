<?php
namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class ImagemTurmaPessoalTest extends DadosPessoaisTest
{
    public function test_imagem_turma_pessoal_apenas_do_proprietario_sem_criar_escola(): void
    {
        Storage::fake('local');
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('grupo.png')])
            ->assertOk()->assertJsonPath('data.tem_foto',true);
        $this->get('/api/v1/turmas/1/foto')->assertOk();
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.tem_foto',true)
            ->assertJsonPath('data.escola_id',null)->assertJsonMissingPath('data.foto_path');
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(2)->noVinculo(2));
        $this->getJson('/api/v1/turmas/1/foto')->assertNotFound();
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'avatar','avatar_id'=>1])->assertNotFound();
    }
}
