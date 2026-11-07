<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Services\ArquivamentoCadastro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;
class ArquivamentoConcorrenciaMariaDbTest extends TestCase {
 use \Tests\Support\BancoCurriculoDescartavel,\Tests\Support\CenarioDisciplinas {\Tests\Support\CenarioDisciplinas::setUp as cenario;}
 protected function setUp():void {
  $this->cenario();if(!in_array(DB::getDriverName(),['mysql','mariadb'],true))$this->markTestSkipped('Exige MariaDB descartável com commits reais.');
  if(!str_ends_with(DB::connection()->getDatabaseName(),'_test'))throw new \RuntimeException('Somente banco descartável.');
 }
 public function test_duas_conexoes_na_mesma_versao_gravam_uma_transicao_e_outra_conflita():void {
  DB::table('alunos')->insert(['id'=>1,'turma_id'=>1,'espaco_id'=>5,'nome'=>'Fictício','matricula'=>'F1']);
  DB::table('usuario_vinculos')->insert(['id'=>6,'usuario_id'=>3,'espaco_id'=>5,'perfil'=>'coordenador','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
  $dir=sys_get_temp_dir().'/g360-arquivo-'.Str::uuid();mkdir($dir,0700);$gate=$dir.'/iniciar';$ps=[];
  $codigo= <<<'PHP'
  require 'vendor/autoload.php';$a=require 'bootstrap/app.php';$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();config(['espacos.ativo'=>true,'disciplinas.ativo'=>true]);Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-11-04 15:00:00','UTC'));
  $u=App\Models\Usuario::findOrFail((int)$argv[1])->noVinculo((int)$argv[2]);file_put_contents($argv[3].'.pronto-'.$argv[2],'1');$fim=microtime(true)+15;while(!is_file($argv[3])){if(microtime(true)>$fim)exit(8);usleep(10000);}
  try{$r=app(App\Services\ArquivamentoCadastro::class)->executar($u,'aluno',1,['arquivado'=>true,'esperado_ativo'=>true,'versao'=>0,'chave'=>$argv[4]]);echo json_encode(['status'=>200]);}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){echo json_encode(['status'=>$e->getStatusCode()]);}
  PHP;
  try{
   foreach([[1,1],[3,6]]as[$uid,$vid]){$p=new Process([PHP_BINARY,'-r',$codigo,(string)$uid,(string)$vid,$gate,(string)Str::uuid()],base_path());$p->setTimeout(30);$p->start();$ps[]=$p;}
   $fim=microtime(true)+15;while(!is_file($gate.'.pronto-1')||!is_file($gate.'.pronto-6')){if(microtime(true)>$fim)$this->fail('Filhos não ficaram prontos.');usleep(10000);}
   touch($gate);foreach($ps as$p)$p->wait();$status=[];foreach($ps as$p){$this->assertTrue($p->isSuccessful(),$p->getErrorOutput());$status[]=json_decode($p->getOutput(),true,512,JSON_THROW_ON_ERROR)['status'];}sort($status);$this->assertSame([200,409],$status);
   $this->assertDatabaseCount('cadastro_arquivamentos',1);$this->assertDatabaseHas('alunos',['id'=>1,'ativo'=>false]);
   $e=DB::table('cadastro_arquivamentos')->first();$nivel=DB::selectOne('SELECT @@session.tx_isolation AS valor')->valor;
   $r=app(ArquivamentoCadastro::class)->executar(Usuario::findOrFail($e->usuario_id)->noVinculo($e->vinculo_id),'aluno',1,['arquivado'=>true,'esperado_ativo'=>true,'versao'=>0,'chave'=>$e->chave]);$this->assertTrue($r['repetido']);
   $this->assertSame($nivel,DB::selectOne('SELECT @@session.tx_isolation AS valor')->valor);$this->assertSame(0,DB::transactionLevel());
  }finally{foreach($ps as$p)if($p->isRunning())$p->stop(1);foreach(glob($dir.'/*')?:[]as$f)unlink($f);rmdir($dir);}
 }
}
