<?php
namespace Tests\Feature;
use App\Models\{Aluno,Usuario};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage,DB};
use Laravel\Sanctum\Sanctum;

class ImagemAlunoPessoalTest extends DadosPessoaisTest
{
    public function test_foto_autorizada_no_pessoal_sem_liberar_dados_vedados_nem_outro_espaco(): void
    {
        Storage::fake('local');
        $id=$this->postJson('/api/v1/alunos',['nome'=>'Aluno fictício','matricula'=>'IMG-1','turma_id'=>1,'foto'=>UploadedFile::fake()->image('retrato.jpg')])
            ->assertCreated()->assertJsonPath('data.tem_foto',true)->assertJsonMissingPath('data.foto_path')->json('data.id');
        $this->get('/api/v1/alunos/'.$id.'/foto')->assertOk();
        $this->postJson('/api/v1/alunos/'.$id.'/imagem',['tipo'=>'avatar','avatar_id'=>10])->assertOk();
        $this->assertDatabaseHas('alunos',['id'=>$id,'cpf'=>null,'cpf_hash'=>null,'nome_responsavel'=>null,'data_nascimento'=>null]);
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::find(2)->noVinculo(2));
        $this->get('/api/v1/alunos/'.$id.'/foto')->assertNotFound();
        $this->postJson('/api/v1/alunos/'.$id.'/imagem',['tipo'=>'avatar','avatar_id'=>1])->assertNotFound();
        // A liberação do retrato não alterou a proteção do banco para outros dados.
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('alunos')->where('id',$id)->update(['genero'=>'M']);
    }
}
