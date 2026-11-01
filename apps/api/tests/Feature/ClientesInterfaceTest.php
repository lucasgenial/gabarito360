<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\{ContextoInterno, Permissao, TokenInterno, TokenVinculo};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ClientesInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['acessos_internos.ativo'=>true, 'espacos.ativo'=>true, 'espacos.pessoal'=>true, 'vinculos.obrigatorio'=>true]);
        Permissao::limparCache();
        foreach ([1,2] as $id) Usuario::forceCreate(['id'=>$id, 'perfil'=>'admin_rede', 'nome'=>'Operador fictício', 'email'=>"a1b-$id@example.test", 'cpf'=>str_pad((string)$id, 11, '0', STR_PAD_LEFT), 'password'=>'ficticio', 'ativo'=>true]);
        foreach (ContextoInterno::PERFIS as $i=>$perfil) DB::table('usuario_acessos_internos')->insert(['id'=>$i+1,'usuario_id'=>1,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        DB::table('usuario_acessos_internos')->insert(['id'=>4,'usuario_id'=>2,'perfil'=>'financeiro','situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'professor','nome'=>'Fictício','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>1,'cliente_id'=>1,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>1]);
        DB::table('usuario_vinculos')->insert(['id'=>1,'usuario_id'=>1,'espaco_id'=>1,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
    }

    private function identidade(): string { return Usuario::find(1)->createToken('api-token')->plainTextToken; }
    private function chamar(string $token, string $metodo, string $url, array $dados = [])
    {
        app('auth')->forgetGuards();
        return $this->json($metodo, '/api/v1/'.$url, $dados, ['Authorization'=>'Bearer '.$token, 'X-Acesso-Interno-Id'=>'4']);
    }
    private function selecionar(string $token, int $id = 3): string
    {
        return $this->chamar($token, 'POST', 'auth/acessos-internos/selecionar', ['acesso_interno_id'=>$id])->assertCreated()->json('data.token');
    }

    public function test_tres_perfis_internos_listam_cadastram_editam_sem_mudar_acessos(): void
    {
        $identidade=$this->identidade();
        foreach ([1,2,3] as $acesso) {
            $token=$this->selecionar($identidade,$acesso);
            $r=$this->chamar($token,'POST','clientes',['tipo'=>'escola','nome'=>'Cliente '.$acesso,'modalidade'=>'teste',
                'documento'=>'DOC-FICTICIO','contato_email'=>'comercial@example.test','contrato_referencia'=>'CONTRATO-TESTE'])->assertCreated();
            $id=$r->json('data.id');
            $this->chamar($token,'GET','clientes/'.$id)->assertOk()->assertJsonPath('data.documento','DOC-FICTICIO')->assertJsonMissingPath('data.documento_hash');
            $this->chamar($token,'GET','clientes?busca=Cliente&modalidade=teste')->assertOk()->assertJsonMissingPath('data.data.0.documento')->assertJsonMissingPath('data.data.0.contato_email');
            $this->chamar($token,'PUT','clientes/'.$id,['modalidade'=>'presenteado','contato_email'=>null])->assertOk();
            $this->chamar($token,'GET','clientes/'.$id)->assertOk()->assertJsonPath('data.modalidade','presenteado')->assertJsonPath('data.contato_email',null);
            $this->assertNotSame('DOC-FICTICIO',DB::table('clientes')->where('id',$id)->value('documento'));
        }
        $this->assertDatabaseCount('espacos',1);
        $this->assertDatabaseCount('usuario_vinculos',1);
        $this->assertDatabaseCount('cliente_eventos',6);
        $this->assertStringNotContainsString('DOC-FICTICIO',DB::table('cliente_eventos')->get()->toJson());
    }

    public function test_identidade_vinculo_e_token_revogado_nao_abrem_cadastro_comercial(): void
    {
        $identidade=$this->identidade();
        $escolar=$this->chamar($identidade,'POST','auth/vinculos/selecionar',['vinculo_id'=>1])->assertCreated()->json('data.token');
        foreach ([$identidade,$escolar] as $token) {
            foreach (['clientes','clientes/1','clientes/1/espacos'] as $url) $this->chamar($token,'GET',$url)->assertForbidden();
            $this->chamar($token,'POST','clientes',['nome'=>'Inválido'])->assertForbidden();
            $this->chamar($token,'PUT','clientes/1',['nome'=>'Inválido'])->assertForbidden();
        }
        $interno=$this->selecionar($identidade);
        $this->chamar($interno,'GET','alunos')->assertForbidden();
        DB::table('usuario_acessos_internos')->where('id',3)->update(['situacao'=>'suspenso','versao_autorizacao'=>2]);
        $this->chamar($interno,'GET','clientes')->assertForbidden();
        $this->assertDatabaseCount('cliente_eventos',0);
    }

    public function test_campos_fechados_validacao_e_busca_paginada(): void
    {
        $t=$this->selecionar($this->identidade());
        foreach (['situacao'=>'suspenso','criado_por'=>2,'espaco_id'=>1,'vinculo_id'=>1] as $campo=>$valor) {
            $this->chamar($t,'PUT','clientes/1',[$campo=>$valor])->assertUnprocessable();
        }
        $this->chamar($t,'POST','clientes',['tipo'=>'invalido','nome'=>'Teste','modalidade'=>'teste'])->assertUnprocessable();
        $this->chamar($t,'PUT','clientes/1',['contato_email'=>'invalido'])->assertUnprocessable();
        $this->chamar($t,'GET','clientes/999')->assertNotFound();
        $this->chamar($t,'GET','clientes?page=0')->assertUnprocessable();
        foreach (range(1,22) as $i) DB::table('clientes')->insert(['tipo'=>'rede','nome'=>'Rede '.$i,'modalidade'=>'teste','situacao'=>'ativo']);
        $this->chamar($t,'GET','clientes?tipo=rede&modalidade=teste')->assertOk()->assertJsonCount(20,'data.data')->assertJsonPath('data.total',22);
        $this->chamar($t,'GET','clientes?tipo=rede&modalidade=teste&page=2')->assertOk()->assertJsonCount(2,'data.data');
        $this->assertDatabaseCount('cliente_eventos',0);
    }

    public function test_espacos_sao_do_cliente_e_so_retornam_contagens_de_vinculos(): void
    {
        DB::table('clientes')->insert(['id'=>2,'tipo'=>'professor','nome'=>'Outro cliente','modalidade'=>'teste','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>2,'cliente_id'=>2,'tipo'=>'pessoal','nome'=>'Outro espaço','situacao'=>'ativo','usuario_proprietario_id'=>2]);
        DB::table('usuario_vinculos')->insert(['usuario_id'=>2,'espaco_id'=>2,'perfil'=>'professor_autonomo','situacao'=>'suspenso','inicio_em'=>now()]);
        $t=$this->selecionar($this->identidade());
        $r=$this->chamar($t,'GET','clientes/1/espacos')->assertOk()->assertJsonCount(1,'data.data')->assertJsonPath('data.data.0.id',1)->assertJsonPath('data.data.0.vinculos.ativo',1);
        $this->assertEqualsCanonicalizing(['id','nome','tipo','situacao','vinculos'],array_keys($r->json('data.data.0')));
        $this->chamar($t,'GET','clientes/2/espacos')->assertOk()->assertJsonCount(1,'data.data')->assertJsonPath('data.data.0.id',2)->assertJsonPath('data.data.0.vinculos.suspenso',1);
        config(['acessos_internos.ativo'=>false]);
        $this->chamar($t,'GET','clientes/1/espacos')->assertForbidden();
    }
}
