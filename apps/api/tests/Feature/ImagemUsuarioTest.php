<?php
namespace Tests\Feature;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage,DB};
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImagemUsuarioTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp():void {
        parent::setUp();Storage::fake('local');
        foreach([1,2] as $id)Usuario::forceCreate(['id'=>$id,'nome'=>'Pessoa fictícia','email'=>$id.'@example.test','cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'perfil'=>'professor','ativo'=>true,'password'=>'ficticio']);
        Sanctum::actingAs(Usuario::find(1));
    }
    public function test_foto_e_reprocessada_privada_e_sem_alterar_outro_usuario():void {
        $this->postJson('/api/v1/auth/imagem',['tipo'=>'foto','usuario_id'=>2,'foto'=>UploadedFile::fake()->image('retrato.jpg',600,400)])->assertOk();
        $u=Usuario::find(1);$this->assertTrue($u->tem_foto);$this->assertNull(Usuario::find(2)->foto_path);
        Storage::disk('local')->assertExists($u->foto_path);
        $info=getimagesizefromstring(Storage::disk('local')->get($u->foto_path));$this->assertSame([384,384],array_slice($info,0,2));$this->assertSame('image/webp',$info['mime']);
        $this->get('/api/v1/auth/foto')->assertOk()->assertHeader('Content-Type','image/webp')->assertHeader('Cache-Control','no-store, private');
        Sanctum::actingAs(Usuario::find(1));
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.tem_foto',true)->assertJsonMissingPath('data.foto_path');
        $this->get('/api/v1/usuarios/2/foto')->assertNotFound();
    }
    public function test_avatar_e_iniciais_substituem_foto_sem_mudar_identidade():void {
        $this->postJson('/api/v1/auth/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('a.png')])->assertOk();$foto=Usuario::find(1)->foto_path;
        $this->postJson('/api/v1/auth/imagem',['tipo'=>'avatar','avatar_id'=>12])->assertOk();
        $this->assertDatabaseHas('usuarios',['id'=>1,'avatar_id'=>12,'foto_path'=>null,'perfil'=>'professor']);Storage::disk('local')->assertMissing($foto);
        $this->postJson('/api/v1/auth/imagem',['tipo'=>'avatar','avatar_id'=>null])->assertOk();$this->assertNull(Usuario::find(1)->avatar_id);
    }
    public function test_arquivo_invalido_avatar_fora_da_galeria_e_foto_ausente_sao_recusados():void {
        $this->postJson('/api/v1/auth/imagem',['tipo'=>'avatar','avatar_id'=>37])->assertUnprocessable();
        $this->postJson('/api/v1/auth/imagem',['tipo'=>'foto'])->assertUnprocessable();
        $this->postJson('/api/v1/auth/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->createWithContent('x.svg','<svg onload="alert(1)"/>')])->assertUnprocessable();
        $this->assertNull(Usuario::find(1)->foto_path);$this->get('/api/v1/auth/foto')->assertNotFound();
    }
    public function test_foto_nao_e_publica():void {
        app('auth')->forgetGuards();
        $this->getJson('/api/v1/auth/foto')->assertUnauthorized();
        $this->getJson('/api/v1/usuarios/1/foto')->assertUnauthorized();
    }
}
