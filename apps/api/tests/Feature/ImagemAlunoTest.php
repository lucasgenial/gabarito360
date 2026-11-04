<?php
namespace Tests\Feature;
use App\Models\{Aluno,Usuario};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class ImagemAlunoTest extends SecretarioEscolarTest
{
    public function test_retrato_privado_exige_permissao_e_escopo_e_preserva_cadastro(): void
    {
        Storage::fake('local');Sanctum::actingAs(Usuario::find(2)->noVinculo(4));
        $antes=Aluno::find(1)->getAttributes();
        $this->post('/api/v1/alunos/1/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('ficticio.png',600,400)],['Accept'=>'application/json'])
            ->assertOk()->assertJsonPath('data.tem_foto',true)->assertJsonPath('data.imagem_versao',2)->assertJsonMissingPath('data.foto_path');
        $aluno=Aluno::find(1);Storage::disk('local')->assertExists($aluno->foto_path);
        foreach(['nome','matricula','turma_id','espaco_id'] as $campo)$this->assertSame($antes[$campo],$aluno->$campo);
        $this->get('/api/v1/alunos/1/foto')->assertOk()->assertHeader('Content-Type','image/webp')->assertHeader('Cache-Control','no-store, private');
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.alunos.0.tem_foto',true)->assertJsonMissingPath('data.alunos.0.foto_path');
        $this->getJson('/api/v1/alunos/1')->assertOk()->assertJsonPath('data.tem_foto',true)->assertJsonMissingPath('data.foto_path');
        $this->postJson('/api/v1/alunos/2/imagem',['tipo'=>'avatar','avatar_id'=>8])->assertNotFound();
        $this->get('/api/v1/alunos/2/foto')->assertNotFound();
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::find(1)->noVinculo(2));
        $this->get('/api/v1/alunos/1/foto')->assertNotFound();
        $this->postJson('/api/v1/alunos/2/imagem',['tipo'=>'avatar','avatar_id'=>8])->assertForbidden();
    }
    public function test_erro_na_foto_preserva_anterior_e_avatar_remove_apenas_a_imagem(): void
    {
        Storage::fake('local');Sanctum::actingAs(Usuario::find(2)->noVinculo(4));
        $this->postJson('/api/v1/alunos/1/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('retrato.jpg')])->assertOk();
        $foto=Aluno::find(1)->foto_path;
        foreach([UploadedFile::fake()->createWithContent('x.svg','<svg/>'),UploadedFile::fake()->image('grande.jpg')->size(2049),UploadedFile::fake()->image('largo.png',4097,1)] as $invalido)
            $this->postJson('/api/v1/alunos/1/imagem',['tipo'=>'foto','foto'=>$invalido])->assertUnprocessable();
        $this->assertSame($foto,Aluno::find(1)->foto_path);Storage::disk('local')->assertExists($foto);
        $this->postJson('/api/v1/alunos/1/imagem',['tipo'=>'avatar','avatar_id'=>8])->assertOk()->assertJsonPath('data.tem_foto',false);
        Storage::disk('local')->assertMissing($foto);$this->assertSame(8,Aluno::find(1)->avatar_id);
        app('auth')->forgetGuards();$this->getJson('/api/v1/alunos/1/foto')->assertUnauthorized();
    }
}
