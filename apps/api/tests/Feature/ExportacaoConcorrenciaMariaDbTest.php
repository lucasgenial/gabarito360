<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ExportacaoConcorrenciaMariaDbTest extends TestCase
{
    use \Tests\Support\PreparaExportacaoTitular {setUp as private setupPadrao;}
    protected function setUp(): void
    {
        parent::setUp();
        if(!in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true))$this->markTestSkipped('Ensaio exige MariaDB descartável.');
        if(!str_ends_with(DB::connection()->getDatabaseName(),'_test'))throw new \RuntimeException('Use banco descartável _test.');
        \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated=false;
        $this->artisan('migrate:fresh',['--force'=>true])->assertSuccessful();
        $this->prepararDadosExportacao();
    }
    private function processo(string $acao,string $id,string $chave=''): Process
    {
        $db=config('database.connections.'.config('database.default'));
        $env=['APP_ENV'=>'testing','APP_KEY'=>config('app.key'),'DB_URL'=>'','DB_CONNECTION'=>config('database.default'),
            'DB_HOST'=>$db['host'],'DB_PORT'=>(string)$db['port'],'DB_DATABASE'=>$db['database'],'DB_USERNAME'=>$db['username'],'DB_PASSWORD'=>$db['password']];
        $p=new Process([PHP_BINARY,base_path('tests/Support/exportacao-worker.php'),$acao,$id,$chave],base_path(),$env,null,20);
        $p->start();return $p;
    }
    private function pronto(Process $p): void
    {
        $limite=microtime(true)+5;
        while(!str_contains($p->getOutput(),'READY') && $p->isRunning() && microtime(true)<$limite)usleep(20000);
        $this->assertStringContainsString('READY',$p->getOutput());
    }
    private function resultado(Process $p): array
    {
        $p->wait();$this->assertTrue($p->isSuccessful(),'Worker deve terminar sem erro.');
        return json_decode(trim(str_replace("READY\n",'',$p->getOutput())),true,512,JSON_THROW_ON_ERROR);
    }
    public function test_geracoes_concorrentes_nao_duplicam_e_revogacao_precede_download_bloqueado(): void
    {
        $p=$this->protocolo();$g=$this->s()->conceder($this->gestor(),$p['referencia'],$this->dados($p));$key=(string)Str::uuid();
        $workers=[];
        DB::beginTransaction();DB::table('acessos_internos_controle')->where('id',1)->lockForUpdate()->first();
        try {
            $workers[]=$this->processo('gerar',$g['referencia'],$key);$workers[]=$this->processo('gerar',$g['referencia'],$key);
            foreach($workers as $w)$this->pronto($w);
            $this->assertDatabaseCount('pedido_exportacao_artefatos',0);
            DB::commit();$a=$this->resultado($workers[0]);$b=$this->resultado($workers[1]);
            $this->assertSame($a,$b);$this->assertDatabaseCount('pedido_exportacao_artefatos',1);
            DB::beginTransaction();DB::table('acessos_internos_controle')->where('id',1)->lockForUpdate()->first();
            $w=$this->processo('baixar',$a['referencia']);$workers[]=$w;$this->pronto($w);
            $this->s()->revogar($this->gestor(),$g['referencia'],['versao'=>1,'motivo'=>'Revogação fictícia','chave'=>(string)Str::uuid()]);
            DB::commit();$this->assertSame(['status'=>403],$this->resultado($w));
            $this->assertSame(0,DB::table('pedido_exportacao_artefato_eventos')->where('acao','download_liberado')->count());
        }finally{
            while(DB::transactionLevel()>0)DB::rollBack();
            foreach($workers as $w)if($w->isRunning())$w->stop();
        }
    }
}
