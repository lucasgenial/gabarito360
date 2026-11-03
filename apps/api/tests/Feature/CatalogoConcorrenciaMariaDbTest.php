<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\{CatalogoSelecao,PrepararCatalogoInstituicoes,PublicarCatalogoInstituicoes};
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Support\ArquivosCatalogo;
use Tests\TestCase;

class CatalogoConcorrenciaMariaDbTest extends TestCase
{
    use ArquivosCatalogo;
    protected function setUp(): void {
        parent::setUp();
        if(!in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true)) $this->markTestSkipped('Exige MariaDB descartável.');
        if(!str_ends_with(DB::connection()->getDatabaseName(),'_test')) throw new \RuntimeException('Use banco descartável _test.');
        RefreshDatabaseState::$migrated=false;$this->artisan('migrate:fresh',['--force'=>true])->assertSuccessful();
        Usuario::forceCreate(['id'=>1,'nome'=>'Professor fictício','perfil'=>'professor','email'=>'concorrencia@example.test','cpf'=>'00000000001','password'=>'ficticio','ativo'=>true]);
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'professor','nome'=>'Cliente fictício','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>1,'cliente_id'=>1,'tipo'=>'pessoal','nome'=>'Pessoal fictício','situacao'=>'ativo','usuario_proprietario_id'=>1]);
        DB::table('usuario_vinculos')->insert(['id'=>1,'usuario_id'=>1,'espaco_id'=>1,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
    }
    protected function tearDown(): void { $this->limparArquivosCatalogo();RefreshDatabaseState::$migrated=false;parent::tearDown(); }
    private function worker(array $e): Process {
        $db=DB::connection()->getConfig();
        $env=['APP_ENV'=>'testing','APP_KEY'=>config('app.key'),'DB_CONNECTION'=>config('database.default'),'DB_URL'=>'',
            'DB_HOST'=>$db['host'],'DB_PORT'=>(string)$db['port'],'DB_DATABASE'=>$db['database'],'DB_USERNAME'=>$db['username'],'DB_PASSWORD'=>$db['password']??'','DB_SOCKET'=>$db['unix_socket']??''];
        $p=new Process([PHP_BINARY,base_path('tests/Support/catalogo-worker.php'),$e['unidade_id'],$e['edicao_id']],base_path(),$env,null,50);$p->start();return $p;
    }
    private function pronto(Process $p): void {
        $fim=microtime(true)+25;
        while(!str_contains($p->getOutput(),'READY') && $p->isRunning() && microtime(true)<$fim) usleep(20000);
        $this->assertStringContainsString('READY',$p->getOutput());
    }
    private function resultado(Process $p): array { $p->wait();$this->assertTrue($p->isSuccessful(),'Worker deve terminar sem erro.');return json_decode(trim(str_replace("READY\n",'',$p->getOutput())),true,512,JSON_THROW_ON_ERROR); }
    public function test_duas_selecoes_concorrentes_e_revogacao_antes_da_gravacao(): void {
        $f=$this->fonteCatalogo();$i=app(PrepararCatalogoInstituicoes::class)->executar(...$f);app(PublicarCatalogoInstituicoes::class)->executar($i['importacao_id'],$f['ufs']);
        $e=app(CatalogoSelecao::class)->buscar(['uf'=>'BA'])['itens'][0];$workers=[];
        DB::beginTransaction();DB::table('usuarios')->where('id',1)->lockForUpdate()->first();
        try {
            $workers[]=$this->worker($e);$workers[]=$this->worker($e);foreach($workers as $w)$this->pronto($w);
            $this->assertDatabaseCount('referencias_instituicoes_pessoais',0);DB::commit();
            $a=$this->resultado($workers[0]);$b=$this->resultado($workers[1]);$this->assertSame($a,$b);
            $this->assertDatabaseCount('referencias_instituicoes_pessoais',1);$this->assertDatabaseCount('catalogo_referencia_eventos',1);
            DB::beginTransaction();DB::table('usuarios')->where('id',1)->lockForUpdate()->first();
            $w=$this->worker($e);$workers[]=$w;$this->pronto($w);
            DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);DB::commit();
            $this->assertSame(['status'=>403],$this->resultado($w));$this->assertDatabaseCount('catalogo_referencia_eventos',1);
        } finally { while(DB::transactionLevel())DB::rollBack();foreach($workers as $w)if($w->isRunning())$w->stop(); }
    }
}
