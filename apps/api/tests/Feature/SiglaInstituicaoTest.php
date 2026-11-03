<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SiglaInstituicaoTest extends TestCase
{
    use RefreshDatabase;
    private Usuario $gestor;

    protected function setUp(): void
    {
        parent::setUp(); config(['espacos.ativo'=>true]); Permissao::limparCache();
        $this->gestor=Usuario::create(['nome'=>'Gestor fictício','perfil'=>'admin_rede','email'=>'sigla@example.test','cpf'=>'00000000001','password'=>'ficticio','ativo'=>true]);
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'rede','nome'=>'Cliente fictício','modalidade'=>'interno','situacao'=>'ativo']);
        foreach ([1,2] as $id) {
            DB::table('redes')->insert(['id'=>$id,'nome'=>'Rede '.$id,'tipo'=>'estadual','uf'=>'BA']);
            DB::table('nucleos')->insert(['id'=>$id,'rede_id'=>$id,'nome'=>'Núcleo '.$id]);
            DB::table('escolas')->insert(['id'=>$id,'nucleo_id'=>$id,'nome'=>'Escola '.$id,'inep'=>'2900000'.$id,'tipo_rede'=>'estadual','sigla'=>'CE-TESTE']);
            foreach(['rede','nucleo','escola'] as $tipo) DB::table('espacos')->insert(['cliente_id'=>1,'tipo'=>$tipo,'nome'=>$tipo.' '.$id,'situacao'=>'ativo',$tipo.'_id'=>$id]);
        }
        DB::table('usuario_escopos')->insert(['usuario_id'=>$this->gestor->id,'escopo_tipo'=>'rede','escopo_id'=>1]);
        Sanctum::actingAs($this->gestor);
    }

    public function test_sigla_repetida_nao_funde_instituicoes_nem_expande_busca(): void
    {
        $this->getJson('/api/v1/escolas?busca=CE-TESTE')->assertOk()->assertJsonCount(1,'data.escolas')->assertJsonPath('data.escolas.0.id',1);
        $this->putJson('/api/v1/escolas/2',['sigla'=>'INDEVIDA'])->assertNotFound();
        $this->assertDatabaseHas('escolas',['id'=>2,'sigla'=>'CE-TESTE']);
        $this->postJson('/api/v1/escolas',['nome'=>'Nova escola','inep'=>'29000003','nucleo_id'=>1,'tipo_rede'=>'estadual','sigla'=>'CE-TESTE'])->assertCreated();
        $this->assertDatabaseCount('escolas',3);
    }

    public function test_sigla_opcional_editavel_sem_trocar_nome_inep_ou_escopo(): void
    {
        $this->putJson('/api/v1/escolas/1',['sigla'=>'CE-CAMPO'])->assertOk();
        $this->assertDatabaseHas('escolas',['id'=>1,'nome'=>'Escola 1','inep'=>'29000001','nucleo_id'=>1,'sigla'=>'CE-CAMPO']);
        $this->putJson('/api/v1/escolas/1',['sigla'=>null])->assertOk();
        $this->assertDatabaseHas('escolas',['id'=>1,'sigla'=>null]);
        $this->putJson('/api/v1/escolas/1',['sigla'=>str_repeat('A',41)])->assertUnprocessable();
        $this->assertDatabaseHas('escolas',['id'=>1,'sigla'=>null]);
    }
}
