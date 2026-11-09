<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\PreferenciasPainel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class EditorPainelConcorrenciaMariaDbTest extends TestCase
{
    use \Tests\Support\BancoCurriculoDescartavel,\Tests\Support\CenarioDisciplinas {\Tests\Support\CenarioDisciplinas::setUp as cenario;}
    protected function setUp():void
    {
        $this->cenario();if(!in_array(DB::getDriverName(),['mysql','mariadb'],true))$this->markTestSkipped('Exige MariaDB descartável com commits reais.');
        if(!str_ends_with(DB::connection()->getDatabaseName(),'_test'))throw new \RuntimeException('Somente banco descartável.');
    }
    public function test_duas_gravacoes_da_mesma_versao_preservam_documento_e_evento_atomicos():void
    {
        $dir=sys_get_temp_dir().'/g360-editor-'.Str::uuid();mkdir($dir,0700);$gate=$dir.'/iniciar';$ps=[];
        $code= <<<'PHP'
        require 'vendor/autoload.php';$a=require 'bootstrap/app.php';$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();config(['espacos.ativo'=>true,'disciplinas.ativo'=>true]);Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-11-04 15:00:00','UTC'));
        $u=App\Models\Usuario::findOrFail(1)->noVinculo(1);$s=app(App\Services\PreferenciasPainel::class);$d=$s->mostrar($u,'turmas',null,true);$c=$d['editor']['composicao'];$c['sections'][0]['title']='Composição '.$argv[1];file_put_contents($argv[2].'.pronto-'.$argv[1],'1');$fim=microtime(true)+15;while(!is_file($argv[2])){if(microtime(true)>$fim)exit(8);usleep(10000);}
        try{$r=$s->gravar($u,'turmas',['versao'=>0,'chave'=>$argv[3],'acao'=>'salvar','catalogo_chave'=>$d['editor']['catalogo_chave'],'composicao'=>$c],null,true);echo json_encode(['status'=>200,'titulo'=>$c['sections'][0]['title']]);}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){echo json_encode(['status'=>$e->getStatusCode()]);}
        PHP;
        try{
            foreach(['A','B'] as $id){$p=new Process([PHP_BINARY,'-r',$code,$id,$gate,(string)Str::uuid()],base_path());$p->setTimeout(30);$p->start();$ps[]=$p;}
            $fim=microtime(true)+15;while(!is_file($gate.'.pronto-A')||!is_file($gate.'.pronto-B')){if(microtime(true)>$fim)$this->fail('Conexões não ficaram prontas.');usleep(10000);}
            touch($gate);$out=[];foreach($ps as $p){$p->wait();$this->assertTrue($p->isSuccessful(),$p->getErrorOutput());$out[]=json_decode($p->getOutput(),true,512,JSON_THROW_ON_ERROR);}
            $statuses=array_column($out,'status');sort($statuses);$this->assertSame([200,409],$statuses);
            $this->assertDatabaseCount('preferencias_paineis',1);$this->assertDatabaseCount('preferencias_painel_eventos',1);
            $row=DB::table('preferencias_paineis')->first();$event=DB::table('preferencias_painel_eventos')->first();$doc=json_decode($row->composicao,true,512,JSON_THROW_ON_ERROR);$audit=json_decode($event->resultado,true,512,JSON_THROW_ON_ERROR);
            $this->assertSame(1,(int)$row->versao);$this->assertSame($doc,$audit['composicao']);$this->assertSame(array_values(array_filter($out,fn($r)=>$r['status']===200))[0]['titulo'],$doc['sections'][0]['title']);
            $actual=app(PreferenciasPainel::class)->mostrar(Usuario::findOrFail(1)->noVinculo(1),'turmas',null,true);$this->assertSame($doc,$actual['editor']['composicao']);$this->assertSame(0,DB::transactionLevel());
        }finally{foreach($ps as $p)if($p->isRunning())$p->stop(1);foreach(glob($dir.'/*')?:[] as $f)unlink($f);rmdir($dir);}
    }
}
