<?php
namespace Tests\Feature;
use App\Models\Usuario;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
class ImagemUsuarioEscopoTest extends SecretarioEscolarTest {
 public function test_imagem_da_equipe_exige_contexto_e_alcance():void {
  Storage::fake('local');Sanctum::actingAs(Usuario::find(2)->noVinculo(4));
  $this->postJson('/api/v1/auth/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('pessoa.png')])->assertOk();
  app('auth')->forgetGuards();Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
  $this->get('/api/v1/usuarios/2/foto')->assertOk();
  $this->getJson('/api/v1/usuarios')->assertOk()->assertJsonPath('data.usuarios.0.tem_foto',true)->assertJsonMissingPath('data.usuarios.0.foto_path');
  app('auth')->forgetGuards();Sanctum::actingAs(Usuario::find(1)->noVinculo(2));
  $this->get('/api/v1/usuarios/2/foto')->assertNotFound();
 }
}
