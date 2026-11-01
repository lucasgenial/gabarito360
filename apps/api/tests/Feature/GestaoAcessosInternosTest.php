<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\GestaoAcessosInternos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GestaoAcessosInternosTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        config(['acessos_internos.ativo'=>true,'vinculos.obrigatorio'=>true]);
        foreach (range(1,6) as $id) Usuario::forceCreate(['id'=>$id,'perfil'=>'admin_rede','nome'=>'Pessoa fictícia',
            'email'=>"b2-$id@example.test",'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>$id!==6]);
        foreach ([1=>'desenvolvedor',3=>'suporte',4=>'financeiro',5=>'desenvolvedor'] as $id=>$perfil) {
            DB::table('usuario_acessos_internos')->insert(['id'=>$id,'usuario_id'=>$id,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
    }
    private function ator(): Usuario { return Usuario::find(1)->noAcessoInterno(1,1); }
    private function dados(): array { return ['email'=>'b2-2@example.test','perfil'=>'suporte','motivo'=>'Ensaio de concessão','fim_em'=>null]; }
    private function chamar(string $t,string $metodo,string $url,array $dados=[])
    {
        app('auth')->forgetGuards();
        return $this->json($metodo,'/api/v1/'.$url,$dados,['Authorization'=>'Bearer '.$t]);
    }
    private function token(int $id): string
    {
        $t = Usuario::find($id)->createToken('api-token')->plainTextToken;
        return $this->chamar($t,'POST','auth/acessos-internos/selecionar',['acesso_interno_id'=>$id])->assertCreated()->json('data.token');
    }
    public function test_api_recusa_identidade_inferiores_e_concede_somente_com_desenvolvedor(): void
    {
        $identidade = Usuario::find(1)->createToken('api-token')->plainTextToken;
        config(['espacos.ativo'=>true,'espacos.pessoal'=>true]);
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'professor','nome'=>'Fictício','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>1,'cliente_id'=>1,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>1]);
        DB::table('usuario_vinculos')->insert(['id'=>1,'usuario_id'=>1,'espaco_id'=>1,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $vinculo = $this->chamar($identidade,'POST','auth/vinculos/selecionar',['vinculo_id'=>1])->assertCreated()->json('data.token');
        foreach ([$identidade,$vinculo,$this->token(3),$this->token(4)] as $t) {
            $this->chamar($t,'GET','acessos-internos')->assertForbidden();
            $this->chamar($t,'POST','acessos-internos',$this->dados())->assertForbidden();
            $this->chamar($t,'PUT','acessos-internos/3',[])->assertForbidden();
        }
        $t = $this->token(1);
        $this->chamar($t,'POST','acessos-internos',$this->dados())->assertCreated()->assertJsonPath('data.usuario_id',2);
        $this->chamar($t,'GET','acessos-internos')->assertOk()->assertJsonCount(3,'data.data');
        $this->assertDatabaseCount('acesso_interno_eventos',1);
        $evento = DB::table('acesso_interno_eventos')->first();
        $this->assertSame(1,$evento->ator_id);
        $this->assertStringNotContainsString('example.test',$evento->atual);
        $this->assertSame('admin_rede',Usuario::find(2)->perfil);
    }
    public function test_recusa_autogestao_pares_perfil_developer_e_contas_inelegiveis(): void
    {
        $t = $this->token(1);
        foreach ([1,5] as $id) $this->chamar($t,'POST','acessos-internos',array_merge($this->dados(),['email'=>"b2-$id@example.test"]))->assertForbidden();
        $this->chamar($t,'POST','acessos-internos',array_merge($this->dados(),['perfil'=>'desenvolvedor']))->assertUnprocessable();
        foreach (['b2-6@example.test','ausente@example.test'] as $email) $this->chamar($t,'POST','acessos-internos',array_merge($this->dados(),['email'=>$email]))->assertUnprocessable();
        Usuario::find(2)->update(['forcar_troca_senha'=>true]);
        $this->chamar($t,'POST','acessos-internos',$this->dados())->assertUnprocessable();
        $this->assertDatabaseCount('acesso_interno_eventos',0);
    }
    public function test_alteracao_exige_versao_preserva_identidade_e_revoga_tokens_antigos(): void
    {
        $dev = $this->token(1); $suporte = $this->token(3);
        $dados = ['situacao'=>'suspenso','fim_em'=>null,'versao_autorizacao'=>1,'motivo'=>'Suspensão no ensaio'];
        $this->chamar($dev,'PUT','acessos-internos/3',$dados)->assertOk()->assertJsonPath('data.versao_autorizacao',2);
        $this->chamar($suporte,'GET','auth/me')->assertForbidden();
        $this->chamar($dev,'PUT','acessos-internos/3',$dados)->assertConflict();
        $this->chamar($dev,'PUT','acessos-internos/3',array_merge($dados,['situacao'=>'ativo','versao_autorizacao'=>2]))->assertOk()->assertJsonPath('data.versao_autorizacao',3);
        $this->chamar($suporte,'GET','auth/me')->assertForbidden();
        $this->chamar($this->token(3),'GET','auth/me')->assertOk();
        $this->chamar($dev,'PUT','acessos-internos/3',array_merge($dados,['perfil'=>'financeiro']))->assertUnprocessable();
        $this->chamar($dev,'PUT','acessos-internos/5',$dados)->assertForbidden();
        $this->assertDatabaseCount('acesso_interno_eventos',2);
        $this->assertDatabaseHas('usuario_acessos_internos',['id'=>3,'usuario_id'=>3,'perfil'=>'suporte']);
    }
    public function test_evento_falhando_desfaz_atribuicao_e_revalidacao_apos_bloqueio_recusa_ator(): void
    {
        $ator = $this->ator();
        DB::listen(function ($q) {
            if (str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'acesso_interno_eventos')) throw new \RuntimeException('Falha fictícia de auditoria');
        });
        try { app(GestaoAcessosInternos::class)->conceder($ator,$this->dados()); $this->fail('Deveria falhar'); }
        catch (\RuntimeException $e) { $this->assertSame('Falha fictícia de auditoria',$e->getMessage()); }
        $this->assertDatabaseMissing('usuario_acessos_internos',['usuario_id'=>2]);
        $this->assertDatabaseCount('acesso_interno_eventos',0);
        DB::listen(function ($q) {
            if (str_starts_with(strtolower($q->sql),'select') && str_contains($q->sql,'acessos_internos_controle')) config(['acessos_internos.ativo'=>false]);
        });
        try { app(GestaoAcessosInternos::class)->conceder($ator,$this->dados()); $this->fail('Deveria recusar'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403,$e->getStatusCode()); }
        $this->assertDatabaseMissing('usuario_acessos_internos',['usuario_id'=>2]);
    }
    public function test_bootstrap_simula_exige_confirmacao_manutencao_e_nao_repete(): void
    {
        $this->artisan('g360:iniciar-desenvolvedor',['usuario'=>'2'])->assertFailed();
        DB::table('usuario_acessos_internos')->where('perfil','desenvolvedor')->delete();
        $this->artisan('g360:iniciar-desenvolvedor',['usuario'=>'2'])->assertSuccessful();
        $this->assertDatabaseMissing('usuario_acessos_internos',['usuario_id'=>2]);
        $this->artisan('g360:iniciar-desenvolvedor',['usuario'=>'2','--aplicar'=>true])->assertFailed();
        $op = ['usuario'=>'2','--aplicar'=>true,'--confirmar-usuario'=>'2','--motivo'=>'Aprovação fictícia B2'];
        $this->artisan('g360:iniciar-desenvolvedor',$op)->assertFailed();
        config(['acessos_internos.ativo'=>false]);
        $this->artisan('g360:iniciar-desenvolvedor',$op)->assertFailed();
        $this->mock(\Illuminate\Contracts\Foundation\MaintenanceMode::class,fn($m)=>$m->shouldReceive('active')->andReturn(true));
        config(['acessos_internos.ativo'=>true]);
        $this->artisan('g360:iniciar-desenvolvedor',$op)->assertFailed();
        config(['acessos_internos.ativo'=>false]);
        $this->artisan('g360:iniciar-desenvolvedor',$op)->assertSuccessful();
        $this->assertDatabaseHas('usuario_acessos_internos',['usuario_id'=>2,'perfil'=>'desenvolvedor']);
        $this->assertDatabaseHas('acesso_interno_eventos',['acao'=>'bootstrap','ator_id'=>null,'origem'=>'console']);
        DB::table('usuario_acessos_internos')->where('usuario_id',2)->update(['situacao'=>'suspenso']);
        $this->artisan('g360:iniciar-desenvolvedor',$op)->assertFailed();
        DB::table('usuario_acessos_internos')->where('usuario_id',2)->update(['perfil'=>'financeiro']);
        $this->artisan('g360:iniciar-desenvolvedor',$op)->assertFailed();
        $this->assertDatabaseCount('acesso_interno_eventos',1);
        $migration = require database_path('migrations/2026_11_01_000009_auditar_gestao_interna.php');
        $migration->up();
        $this->assertNotNull(DB::table('acessos_internos_controle')->value('bootstrap_em'));
    }
}
