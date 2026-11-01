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

class SelecaoVinculoTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo' => true, 'vinculos.obrigatorio' => true]);
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


    private function token(): string
    {
        return Usuario::find(1)->createToken('api-token')->plainTextToken;
    }

    private function chamar(string $token, string $method, string $url, array $data = [])
    {
        app('auth')->forgetGuards();
        return $this->json($method, '/api/v1/'.$url, $data, ['Authorization' => 'Bearer '.$token, 'X-Vinculo-Id' => '3']);
    }

    private function selecionar(string $token, int $id): string
    {
        return $this->chamar($token, 'POST', 'auth/vinculos/selecionar', ['vinculo_id' => $id])
            ->assertCreated()->json('data.token');
    }

    public function test_lista_so_proprios_validos_e_recusa_alheio_sem_criar_token(): void
    {
        $t = $this->token();
        $this->chamar($t, 'GET', 'auth/vinculos')->assertOk()->assertJsonCount(2, 'data');
        $this->chamar($t, 'POST', 'auth/vinculos/selecionar', ['vinculo_id' => 3])->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        DB::table('usuario_vinculos')->where('id', 1)->update(['situacao' => 'suspenso']);
        $this->chamar($t, 'GET', 'auth/vinculos')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 2);
        DB::table('espacos')->where('id', 6)->update(['situacao' => 'suspenso']);
        $this->chamar($t, 'GET', 'auth/vinculos')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_dois_tokens_preservam_contextos_sem_alterar_identidade_ou_aceitar_header(): void
    {
        $t = $this->token(); $a = $this->selecionar($t, 1); $b = $this->selecionar($t, 2);
        foreach ([[$a, 'dir_escolar'], [$b, 'professor'], [$a, 'dir_escolar']] as [$token, $perfil]) {
            $this->chamar($token, 'GET', 'auth/me?vinculo_id=3')->assertOk()->assertJsonPath('data.perfil', $perfil);
        }
        $this->chamar($a, 'GET', 'turmas')->assertOk()->assertJsonPath('data.turmas.0.id', 1)->assertJsonCount(1, 'data.turmas');
        $this->chamar($b, 'GET', 'turmas')->assertOk()->assertJsonPath('data.turmas.0.id', 2)->assertJsonCount(1, 'data.turmas');
        $this->chamar($t, 'GET', 'auth/me')->assertOk()->assertJsonPath('data.perfil', null);
        $this->chamar($t, 'GET', 'turmas?vinculo_id=1')->assertForbidden();
        $this->chamar($a, 'POST', 'auth/vinculos/selecionar', ['vinculo_id' => 2])->assertForbidden();
        $this->assertSame('admin_rede', Usuario::find(1)->perfil);
        DB::table('usuario_vinculos')->where('id', 1)->update(['situacao' => 'suspenso']);
        $this->chamar($a, 'GET', 'turmas')->assertForbidden();
        $this->chamar($b, 'GET', 'turmas')->assertOk();
    }

    public function test_logout_contextual_nao_encerra_outro_mas_logout_identidade_invalida_filhos(): void
    {
        $t = $this->token(); $a = $this->selecionar($t, 1); $b = $this->selecionar($t, 2);
        $this->chamar($a, 'POST', 'auth/logout')->assertOk();
        $this->chamar($a, 'GET', 'auth/me')->assertUnauthorized();
        $this->chamar($b, 'GET', 'auth/me')->assertOk();
        $this->chamar($t, 'POST', 'auth/logout')->assertOk();
        $this->chamar($b, 'GET', 'turmas')->assertForbidden();
        $this->chamar($b, 'POST', 'auth/logout')->assertOk();
    }

    public function test_metadados_invalidos_pai_alheio_e_pai_expirado_sao_recusados(): void
    {
        $t = $this->token(); $a = $this->selecionar($t, 1);
        $modelo = \Laravel\Sanctum\PersonalAccessToken::findToken($a);
        $original = $modelo->abilities;
        $modelo->forceFill(['abilities' => ['*']])->save();
        $this->chamar($a, 'GET', 'auth/me')->assertForbidden();
        $alheio = Usuario::find(2)->createToken('api-token')->accessToken;
        $modelo->forceFill(['abilities' => ['vinculo:1', 'identidade:'.$alheio->id]])->save();
        $this->chamar($a, 'GET', 'auth/me')->assertForbidden();
        $modelo->forceFill(['abilities' => $original])->save();
        \Laravel\Sanctum\PersonalAccessToken::findToken($t)->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->chamar($a, 'GET', 'auth/me')->assertForbidden();
    }

    public function test_troca_obrigatoria_nao_pode_ser_contornada_por_selecao(): void
    {
        Usuario::find(1)->update(['forcar_troca_senha' => true]);
        $t = Usuario::find(1)->createToken('troca-senha')->plainTextToken;
        $this->chamar($t, 'GET', 'auth/vinculos')->assertForbidden();
        $this->chamar($t, 'POST', 'auth/vinculos/selecionar', ['vinculo_id' => 1])->assertForbidden();
        $this->chamar($t, 'GET', 'auth/me')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_contexto_nao_volta_ao_legado_com_regime_desligado(): void
    {
        $t = $this->token(); $a = $this->selecionar($t, 1);
        config(['vinculos.obrigatorio' => false]);
        $this->chamar($t, 'GET', 'auth/vinculos')->assertNotFound();
        $this->chamar($a, 'GET', 'auth/me')->assertOk()->assertJsonPath('data.perfil', 'dir_escolar');
        DB::table('usuario_vinculos')->where('id', 1)->update(['fim_em' => now()]);
        $this->chamar($a, 'GET', 'auth/me')->assertForbidden();
    }
}
