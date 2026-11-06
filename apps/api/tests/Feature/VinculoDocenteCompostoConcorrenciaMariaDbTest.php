<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\GestaoDisciplinas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class VinculoDocenteCompostoConcorrenciaMariaDbTest extends TestCase
{
    use \Tests\Support\BancoCurriculoDescartavel,\Tests\Support\CenarioDisciplinas {
        \Tests\Support\CenarioDisciplinas::setUp as cenario;
    }
    protected function setUp():void
    {
        $this->cenario();
        if(!in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true))$this->markTestSkipped('Exige MariaDB descartável e transações confirmadas.');
        if(!str_ends_with(DB::connection()->getDatabaseName(),'_test'))throw new \RuntimeException('Exige banco descartável _test.');
    }

    public function test_dois_atores_disputam_componente_ausente_sem_efeitos_parciais_do_perdedor():void
    {
        DB::table('usuario_vinculos')->insert(['id'=>6,'usuario_id'=>3,'espaco_id'=>5,'perfil'=>'coordenador','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        DB::table('vinculo_turmas')->where('vinculo_id',3)->delete();
        $d=app(GestaoDisciplinas::class)->salvar(Usuario::findOrFail(1)->noVinculo(1),['nome'=>'Concorrência composta','ativo'=>true]);
        $dir=sys_get_temp_dir().'/g360-docente-'.Str::uuid();mkdir($dir,0700);$gate=$dir.'/iniciar';
        $codigo= <<<'PHP'
        require 'vendor/autoload.php';$a=require 'bootstrap/app.php';$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['espacos.ativo'=>true,'disciplinas.ativo'=>true]);Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-11-04 15:00:00','UTC'));
        $u=App\Models\Usuario::findOrFail((int)$argv[1])->noVinculo((int)$argv[2]);
        file_put_contents($argv[4].'.pronto-'.$argv[2],'1');$limite=microtime(true)+15;
        while(!is_file($argv[4])){if(microtime(true)>$limite)exit(8);usleep(10000);}
        try{$r=app(App\Services\VinculoDocenteComposto::class)->executar($u,1,['disciplina_id'=>(int)$argv[3],'professor_vinculo_id'=>3,'chave_idempotencia'=>$argv[5]]);echo json_encode(['status'=>201,'atribuicao_id'=>$r['atribuicao_id']]);}
        catch(Illuminate\Validation\ValidationException $e){echo json_encode(['status'=>422]);}
        catch(Symfony\Component\HttpKernel\Exception\HttpException $e){echo json_encode(['status'=>$e->getStatusCode()]);}
        PHP;
        $processos=[];
        try {
            foreach([[1,1],[3,6]]as[$usuario,$vinculo]){
                $p=new Process([PHP_BINARY,'-r',$codigo,(string)$usuario,(string)$vinculo,(string)$d->id,$gate,(string)Str::uuid()],base_path());
                $p->setTimeout(30);$p->start();$processos[]=$p;
            }
            $limite=microtime(true)+15;
            while(!is_file($gate.'.pronto-1')||!is_file($gate.'.pronto-6')){
                if(microtime(true)>$limite)$this->fail('Filhos não ficaram prontos.');usleep(10000);
            }
            touch($gate);$status=[];
            // Aguardar ambos antes de assertions/cleanup; nunca encerrar um vencedor ainda em commit.
            foreach($processos as$p)$p->wait();
            foreach($processos as$p){$this->assertTrue($p->isSuccessful(),$p->getErrorOutput());$status[]=json_decode($p->getOutput(),true,512,JSON_THROW_ON_ERROR)['status'];}
            sort($status);$this->assertSame([201,409],$status);
            foreach(['turma_disciplinas','disciplina_professores','vinculo_turmas','docente_atribuicoes','vinculos_docentes_compostos','vinculo_eventos']as$t)$this->assertDatabaseCount($t,1);
            $nivel=DB::selectOne('SELECT @@session.tx_isolation AS nivel')->nivel;
            $recibo=DB::table('vinculos_docentes_compostos')->first();
            $ator=DB::table('usuario_vinculos')->find($recibo->ator_vinculo_id);
            $replay=app(\App\Services\VinculoDocenteComposto::class)->executar(Usuario::findOrFail($ator->usuario_id)->noVinculo($ator->id),1,
                ['disciplina_id'=>$d->id,'professor_vinculo_id'=>3,'chave_idempotencia'=>$recibo->chave]);
            $this->assertTrue($replay['repetido']);
            $this->assertSame($nivel,DB::selectOne('SELECT @@session.tx_isolation AS nivel')->nivel);
            $this->assertSame(0,DB::transactionLevel());
        } finally {
            foreach($processos as$p)if($p->isRunning())$p->stop(1);
            foreach(glob($dir.'/*')?:[]as$f)unlink($f);rmdir($dir);
        }
    }
}
