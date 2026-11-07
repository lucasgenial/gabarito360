<?php
namespace Tests\Feature;

use App\Models\{Turma,Usuario};
use App\Services\ImagemPerfil;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB,Event,Storage};
use Laravel\Sanctum\Sanctum;

class ImagemTurmaTest extends SecretarioEscolarTest
{
    private function secretaria(): void
    {
        Storage::fake('local');
        Sanctum::actingAs(Usuario::findOrFail(2)->noVinculo(4));
    }

    public function test_foto_turma_privada_normalizada_e_dto_sem_caminho_preservam_cadastro(): void
    {
        $this->secretaria();$antes=Turma::findOrFail(1)->getAttributes();
        $this->post('/api/v1/turmas/1/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('turma.png',600,400)],['Accept'=>'application/json'])
            ->assertOk()->assertJsonPath('data.tem_foto',true)->assertJsonPath('data.imagem_versao',2)
            ->assertJsonPath('data.avatar_id',null)->assertJsonMissingPath('data.foto_path');
        $turma=Turma::findOrFail(1);$bytes=Storage::disk('local')->get($turma->foto_path);
        $info=getimagesizefromstring($bytes);$this->assertSame('image/webp',$info['mime']);
        $this->assertSame([384,384],[$info[0],$info[1]]);$this->assertLessThanOrEqual(ImagemPerfil::MAX_BYTES,strlen($bytes));
        foreach($antes as$campo=>$valor)if(!in_array($campo,['foto_path','avatar_id','imagem_versao','updated_at']))$this->assertSame($valor,$turma->getRawOriginal($campo));
        $this->get('/api/v1/turmas/1/foto')->assertOk()->assertHeader('Content-Type','image/webp')
            ->assertHeader('Cache-Control','no-store, private')->assertHeader('X-Content-Type-Options','nosniff')->assertContent($bytes);
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.tem_foto',true)
            ->assertJsonPath('data.imagem_versao',2)->assertJsonMissingPath('data.foto_path');
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonPath('data.turmas.0.tem_foto',true)->assertJsonMissingPath('data.turmas.0.foto_path');
    }

    public function test_avatar_iniciais_limites_e_erros_preservam_imagem_anterior(): void
    {
        $this->secretaria();
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('original.jpg')])->assertOk();
        $foto=Turma::findOrFail(1)->foto_path;
        foreach([UploadedFile::fake()->createWithContent('x.svg','<svg/>'),UploadedFile::fake()->image('grande.jpg')->size(2049),UploadedFile::fake()->image('largo.png',4097,1)]as$invalido)
            $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'foto','foto'=>$invalido])->assertUnprocessable();
        foreach([0,37]as$id)$this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'avatar','avatar_id'=>$id])->assertUnprocessable();
        $this->assertSame($foto,Turma::findOrFail(1)->foto_path);Storage::disk('local')->assertExists($foto);
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'avatar','avatar_id'=>36])->assertOk()->assertJsonPath('data.avatar_id',36)->assertJsonPath('data.tem_foto',false);
        Storage::disk('local')->assertMissing($foto);
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'avatar','avatar_id'=>null])->assertOk()->assertJsonPath('data.avatar_id',null);
        $this->getJson('/api/v1/turmas/1/foto')->assertNotFound();
    }

    public function test_leitor_nao_altera_escopo_nao_vaza_e_arquivada_aceita_so_metadado(): void
    {
        $this->secretaria();
        DB::table('turmas')->where('id',1)->update(['ativo'=>false]);
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('f.jpg')])->assertOk();
        $this->assertFalse(Turma::findOrFail(1)->ativo);$this->assertSame(0,DB::table('cadastro_arquivamentos')->count());
        $this->postJson('/api/v1/turmas/2/imagem',['tipo'=>'avatar','avatar_id'=>1])->assertNotFound();
        $this->getJson('/api/v1/turmas/2/foto')->assertNotFound();
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>3,'espaco_id'=>5,'turma_id'=>1]);
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(2)->noVinculo(3));
        $this->get('/api/v1/turmas/1/foto')->assertOk();
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'avatar','avatar_id'=>1])->assertForbidden();
        DB::table('vinculo_turmas')->where('vinculo_id',3)->delete();
        $this->getJson('/api/v1/turmas/1/foto')->assertNotFound();
        app('auth')->forgetGuards();$this->getJson('/api/v1/turmas/1/foto')->assertUnauthorized();
    }

    public function test_revogacao_durante_normalizacao_recusa_e_remove_so_novo_arquivo(): void
    {
        $this->secretaria();
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('antes.jpg')])->assertOk();
        $foto=Turma::findOrFail(1)->foto_path;
        app()->instance(ImagemPerfil::class,new class extends ImagemPerfil {
            public function guardar(UploadedFile $foto,string $pasta):string {
                $p=parent::guardar($foto,$pasta);
                DB::table('usuario_vinculos')->where('id',4)->update(['situacao'=>'suspenso','versao_autorizacao'=>2]);
                return $p;
            }
        });
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('depois.jpg')])->assertForbidden();
        $this->assertSame($foto,Turma::findOrFail(1)->foto_path);Storage::disk('local')->assertExists($foto);
        $this->assertCount(1,Storage::disk('local')->allFiles('turmas/fotos'));
    }

    public function test_falha_da_gravacao_reverte_versao_e_preserva_foto_anterior(): void
    {
        $this->secretaria();
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('antes.jpg')])->assertOk();
        $antes=Turma::findOrFail(1);$bytes=Storage::disk('local')->get($antes->foto_path);
        $evento='eloquent.updating: '.Turma::class;
        Event::listen($evento,static function(){throw new \RuntimeException('Falha fictícia de persistência.');});
        $this->withoutExceptionHandling();
        try {
            $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('depois.jpg')]);
            $this->fail('A persistência deveria falhar.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Falha fictícia de persistência.',$e->getMessage());
        } finally { Event::forget($evento); }
        $atual=Turma::findOrFail(1);
        $this->assertSame($antes->foto_path,$atual->foto_path);
        $this->assertSame($antes->imagem_versao,$atual->imagem_versao);
        $this->assertSame($bytes,Storage::disk('local')->get($atual->foto_path));
        $this->assertCount(1,Storage::disk('local')->allFiles('turmas/fotos'));
        $this->assertSame(0,DB::transactionLevel());
    }

    public function test_cotas_imagem_turma_usuario_e_aluno_nao_colidem_e_nao_aceita_caminho_arbitrario(): void
    {
        $this->secretaria();
        for($i=0;$i<10;$i++)$this->postJson('/api/v1/auth/imagem',['tipo'=>'avatar','avatar_id'=>1])->assertOk();
        for($i=0;$i<10;$i++)$this->postJson('/api/v1/alunos/1/imagem',['tipo'=>'avatar','avatar_id'=>1])->assertOk();
        for($i=0;$i<10;$i++)$this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'avatar','avatar_id'=>1])->assertOk();
        $this->postJson('/api/v1/turmas/1/imagem',['tipo'=>'avatar','avatar_id'=>2])->assertStatus(429)->assertHeader('Retry-After');
        Storage::disk('local')->put('usuarios/fotos/nao-da-turma.webp','privado');
        DB::table('turmas')->where('id',1)->update(['foto_path'=>'usuarios/fotos/nao-da-turma.webp']);
        $this->getJson('/api/v1/turmas/1/foto')->assertNotFound();
        Storage::disk('local')->assertExists('usuarios/fotos/nao-da-turma.webp');
    }
}
