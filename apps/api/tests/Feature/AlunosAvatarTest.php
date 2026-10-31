<?php
namespace Tests\Feature;

use App\Models\Aluno;
use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlunosAvatarTest extends TestCase
{
    use RefreshDatabase;
    private array $dados;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Permissao::limparCache();
        Sanctum::actingAs(Usuario::create(['perfil'=>'admin_rede','nome'=>'Gestor fictício',
            'cpf'=>'00000000123','email'=>'avatar@example.test','password'=>'senha-ficticia','ativo'=>true]));
        $rede=DB::table('redes')->insertGetId(['nome'=>'Rede fictícia','tipo'=>'estadual','uf'=>'BA']);
        $nucleo=DB::table('nucleos')->insertGetId(['rede_id'=>$rede,'nome'=>'Núcleo fictício']);
        $escola=DB::table('escolas')->insertGetId(['nucleo_id'=>$nucleo,'nome'=>'Escola fictícia','inep'=>'29000001','tipo_rede'=>'estadual']);
        $turma=DB::table('turmas')->insertGetId(['escola_id'=>$escola,'nome'=>'3A','serie'=>'3ª série','turno'=>'tarde','ano_letivo'=>2026]);
        $this->dados=['turma_id'=>$turma,'nome'=>'Ágata Érica','matricula'=>'FICTICIO-1','data_nascimento'=>'2010-01-01'];
    }

    public function test_cadastro_rejeita_upload_e_caminho_sem_criar_aluno_ou_arquivo(): void
    {
        foreach (['foto'=>UploadedFile::fake()->image('foto.jpg'),'foto_path'=>'alunos/foto.jpg'] as $campo=>$valor) {
            $this->post('/api/v1/alunos', $this->dados+[$campo=>$valor], ['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors($campo);
        }
        $this->assertDatabaseCount('alunos',0);
        $this->assertSame([],Storage::disk('public')->allFiles());
    }

    public function test_edicao_rejeita_foto_preserva_legado_e_respostas_nao_expoem_caminho(): void
    {
        $aluno=Aluno::create($this->dados);
        DB::table('alunos')->where('id',$aluno->id)->update(['foto_path'=>'alunos/legado.jpg']);
        Storage::disk('public')->put('alunos/legado.jpg','conteudo ficticio preservado');
        foreach (['foto'=>UploadedFile::fake()->image('nova.jpg'),'foto_path'=>'alunos/nova.jpg'] as $campo=>$valor) {
            $this->put('/api/v1/alunos/'.$aluno->id,[$campo=>$valor,'nome'=>'Não salvar'], ['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors($campo);
        }
        $this->assertSame($this->dados['nome'],$aluno->fresh()->nome);
        $this->putJson('/api/v1/alunos/'.$aluno->id,['nome'=>'Ágata Almeida'])->assertOk()->assertJsonMissingPath('data.foto_path');
        $this->getJson('/api/v1/alunos/'.$aluno->id)->assertOk()->assertJsonMissingPath('data.foto_path');
        $this->getJson('/api/v1/alunos')->assertOk()->assertJsonMissingPath('data.alunos.0.foto_path');
        $this->assertSame('alunos/legado.jpg',$aluno->fresh()->foto_path);
        $this->assertSame('conteudo ficticio preservado',Storage::disk('public')->get('alunos/legado.jpg'));
        $this->assertCount(1,Storage::disk('public')->allFiles());
        $this->assertFalse($aluno->isFillable('foto_path'));
    }

    public function test_cadastro_sem_foto_continua_funcionando(): void
    {
        $this->postJson('/api/v1/alunos',$this->dados)->assertCreated()->assertJsonPath('data.nome','Ágata Érica')->assertJsonMissingPath('data.foto_path');
        $this->assertDatabaseCount('alunos',1);
        $this->assertNull(Aluno::sole()->foto_path);
    }
}
