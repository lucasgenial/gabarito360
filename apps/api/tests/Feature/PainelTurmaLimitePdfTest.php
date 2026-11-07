<?php
namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PainelTurmaLimitePdfTest extends TestCase
{
    use RefreshDatabase,\Tests\Support\CenarioDisciplinas {
        \Tests\Support\CenarioDisciplinas::setUp as setUpCenario;
    }

    private array $arquivos=[];

    protected function setUp():void
    {
        // O setUp do cenário deve preceder configuração do processo, sem desativar middleware.
        $this->setUpCenario();
        config(['documentos.validacao_url'=>'https://example.test/validar-documento']);
        Process::fake(function($p){
            $d=json_decode($p->input,true,512,JSON_THROW_ON_ERROR);
            $this->arquivos[]=$d['saida'];file_put_contents($d['saida'],'%PDF-ficticio');
            return Process::result(output:'{"ok":true}');
        });
    }

    protected function tearDown():void
    {
        foreach($this->arquivos as$f)if(is_file($f))unlink($f);
        parent::tearDown();
    }

    public function test_consultas_reais_nao_consumem_a_cota_de_emissao_do_painel():void
    {
        for($i=0;$i<20;$i++)$this->getJson('/api/v1/disciplinas/opcoes')->assertOk();
        $this->getJson('/api/v1/turmas/1/painel/pdf')->assertOk()->assertHeader('Content-Type','application/pdf');
        $this->assertDatabaseCount('documento_emissoes',1);
    }

    public function test_sexta_emissao_bloqueia_e_cota_reabre_apos_um_minuto():void
    {
        for($i=0;$i<5;$i++)$this->getJson('/api/v1/turmas/1/painel/pdf')->assertOk();
        $this->getJson('/api/v1/turmas/1/painel/pdf')->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertDatabaseCount('documento_emissoes',5);
        $this->travel(61)->seconds();
        $this->getJson('/api/v1/turmas/1/painel/pdf')->assertOk();
    }

    public function test_cota_e_do_usuario_e_espaco_sem_reiniciar_na_troca_de_perfil():void
    {
        for($i=0;$i<5;$i++)$this->getJson('/api/v1/turmas/1/painel/pdf')->assertOk();
        $this->como(1,4); // Mesmo usuário/escola, outro perfil: conserva a cota.
        $this->getJson('/api/v1/turmas/1/painel/pdf')->assertTooManyRequests();
        $this->como(1,2); // Mesmo usuário, escola distinta: outra cota.
        $this->getJson('/api/v1/turmas/2/painel/pdf')->assertOk();
        $this->como(2,3); // Outro usuário na escola original: outra cota.
        $this->getJson('/api/v1/turmas/1/painel/pdf')->assertOk();
    }

    private function como(int $usuario,int $vinculo):void
    {
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail($usuario)->noVinculo($vinculo));
    }
}
