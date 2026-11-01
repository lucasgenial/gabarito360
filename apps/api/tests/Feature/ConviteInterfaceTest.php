<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\ConciliarVinculos;
use App\Services\ConviteVinculo;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConviteInterfaceTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo' => true]);
        $this->travelTo(now()->startOfSecond());
        Permissao::limparCache();
        foreach ([1, 2] as $id) {
            Usuario::forceCreate(['id' => $id, 'perfil' => 'admin_rede', 'nome' => 'Pessoa fictícia', 'email' => $id.'@example.test', 'cpf' => str_pad((string) $id, 11, '0', STR_PAD_LEFT), 'password' => 'ficticio', 'ativo' => true]);
            DB::table('clientes')->insert(['id' => $id, 'tipo' => 'rede', 'nome' => 'Cliente', 'modalidade' => 'interno', 'situacao' => 'ativo']);
            DB::table('redes')->insert(['id' => $id, 'nome' => 'Rede', 'tipo' => 'estadual', 'uf' => 'BA']);
            DB::table('nucleos')->insert(['id' => $id, 'rede_id' => $id, 'nome' => 'Núcleo']);
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => $id, 'nome' => 'Escola', 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            foreach (['rede' => 0, 'nucleo' => 2, 'escola' => 4] as $tipo => $offset) {
                DB::table('espacos')->insert(['id' => $id + $offset, 'cliente_id' => $id, 'tipo' => $tipo, 'nome' => 'Espaço', 'situacao' => 'ativo', $tipo.'_id' => $id]);
            }
            DB::table('usuario_escopos')->insert(['usuario_id' => 1, 'escopo_tipo' => 'rede', 'escopo_id' => $id]);
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            DB::table('alunos')->insert(['id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 4, 'nome' => 'Fictício', 'matricula' => 'C-'.$id]);
            DB::table('provas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'criado_por' => 1,
                'titulo' => 'Prova', 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 4]);
            DB::table('prova_turmas')->insert(['prova_id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 4]);
        }
        $this->vinculo(1, 1, 5, 'dir_escolar');
        $this->vinculo(2, 1, 6, 'professor');
        $this->vinculo(3, 2, 5, 'professor');
        DB::table('vinculo_turmas')->insert(['vinculo_id' => 2, 'espaco_id' => 6, 'turma_id' => 2]);
    }

    private function vinculo(int $id, int $usuario, int $espaco, string $perfil): void
    {
        DB::table('usuario_vinculos')->insert(['id' => $id, 'usuario_id' => $usuario, 'espaco_id' => $espaco, 'perfil' => $perfil,
            'situacao' => 'ativo', 'inicio_em' => now()->subDay(), 'versao_autorizacao' => 1]);
    }


    public function test_opcoes_limitam_destinos_perfis_e_turmas_ao_gestor(): void
    {
        Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
        $r = $this->getJson('/api/v1/usuarios/convites-vinculo/opcoes')->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame(5, $r->json('data.0.id'));
        $this->assertNotContains('admin_rede', $r->json('data.0.perfis'));
        $this->assertNotContains('dir_escolar', $r->json('data.0.perfis'));
        $this->assertSame([1], array_column($r->json('data.0.turmas'), 'id'));
        Sanctum::actingAs(Usuario::find(1)->noVinculo(2));
        $this->getJson('/api/v1/usuarios/convites-vinculo/opcoes')->assertForbidden();
    }

    public function test_convite_email_consulta_aceite_e_selecao_com_tokens_reais(): void
    {
        config(['vinculos.obrigatorio' => true]); Mail::fake();
        $gestor = Usuario::find(1)->createToken('api-token')->plainTextToken;
        $t = $this->chamar($gestor, 'POST', 'auth/vinculos/selecionar', ['vinculo_id'=>1])->assertCreated()->json('data.token');
        $this->chamar($t, 'POST', 'usuarios/convites-vinculo', ['email'=>'2@example.test','espaco_id'=>5,'perfil'=>'coordenador'])->assertStatus(202);
        $mail = Mail::queued(\App\Mail\EmailModelo::class)->first();
        parse_str(parse_url($mail->dados['link'], PHP_URL_FRAGMENT), $link);
        $this->assertNull(parse_url($mail->dados['link'], PHP_URL_QUERY));
        $dono = Usuario::find(2)->createToken('api-token')->plainTextToken;
        $base = 'auth/convites-vinculo/'.$link['convite'];
        $this->chamar($gestor, 'POST', $base.'/consultar', ['token'=>$link['token']])->assertUnprocessable();
        $this->chamar($dono, 'POST', $base.'/consultar', ['token'=>$link['token']])->assertOk()->assertJsonPath('data.funcao','Coordenador Pedagógico');
        $senha = Usuario::find(2)->getRawOriginal('password');
        $this->chamar($dono, 'POST', $base.'/responder', ['token'=>$link['token'],'aceitar'=>true])->assertOk();
        $this->assertSame($senha, Usuario::find(2)->getRawOriginal('password'));
        $this->assertDatabaseHas('usuario_vinculos',['id'=>3,'situacao'=>'ativo']);
        $id = DB::table('usuario_vinculos')->where('usuario_id',2)->where('perfil','coordenador')->value('id');
        $this->chamar($dono,'POST','auth/vinculos/selecionar',['vinculo_id'=>$id])->assertCreated()->assertJsonPath('data.perfil','coordenador');
        $this->chamar($dono, 'POST', $base.'/responder', ['token'=>$link['token'],'aceitar'=>true])->assertUnprocessable();
    }

    public function test_reenvio_preserva_turmas_recebidas_como_strings_do_formulario(): void
    {
        Mail::fake();
        DB::table('usuario_vinculos')->where('id',3)->update(['perfil'=>'aplicador']);
        Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
        $dados=['email'=>'2@example.test','espaco_id'=>5,'perfil'=>'professor','turma_ids'=>['1']];
        $this->postJson('/api/v1/usuarios/convites-vinculo',$dados)->assertStatus(202);
        $this->postJson('/api/v1/usuarios/convites-vinculo',$dados)->assertStatus(202);
        Mail::assertQueued(\App\Mail\EmailModelo::class,2);
        $this->assertDatabaseCount('vinculo_convites',2);
        $this->assertDatabaseCount('vinculo_turmas',2);
    }

    public function test_primeiro_acesso_vencido_continua_disponivel_para_reenvio(): void
    {
        Mail::fake();
        DB::table('usuario_vinculos')->where('id',3)->update(['situacao'=>'convidado']);
        $gestor=Usuario::find(1)->noVinculo(1);
        app(\App\Services\ContaUsuario::class)->convidar(Usuario::find(2),$gestor,3);
        $this->travel(8)->days();
        Sanctum::actingAs($gestor);
        $this->getJson('/api/v1/usuarios/2?vinculo_id=3')->assertOk()->assertJsonPath('data.convite_primeiro_acesso',true);
        $this->postJson('/api/v1/usuarios/2/convite',['vinculo_id'=>3])->assertOk();
        Mail::assertQueued(\App\Mail\EmailModelo::class,2);
    }

    private function chamar(string $t, string $metodo, string $url, array $dados = [])
    {
        app('auth')->forgetGuards();
        return $this->json($metodo,'/api/v1/'.$url,$dados,['Authorization'=>'Bearer '.$t]);
    }
}
