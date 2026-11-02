<?php
namespace Tests\Feature;

use App\Jobs\GravarAuditoria;
use App\Logging\ProtegerDados;
use App\Services\ConferenciaAuditoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Monolog\{Level, LogRecord};
use Tests\TestCase;

class ConferenciaAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private function evento(string $id, string $mes): array
    {
        return ['id'=>$id,'mes'=>$mes,'ocorrido_em'=>$mes.'-10 12:34:56.123456','metodo'=>'GET',
            'rota'=>'api/v1/alunos/{id}','referencias'=>'{"id":"1"}','status_http'=>200,'resultado'=>'sucesso'];
    }
    public function test_retentencao_nao_presume_prazo_e_preservacao_bloqueia_mes_inteiro(): void
    {
        $this->travelTo(now()->setDate(2026,11,1)->startOfDay());
        foreach (['2026-01','2026-04','2026-05','2026-10'] as $mes) (new GravarAuditoria($this->evento((string)\Illuminate\Support\Str::uuid(),$mes)))->handle();
        config(['auditoria.retencao_dias'=>null,'auditoria.meses_preservados'=>['2026-01']]);
        $s=app(ConferenciaAuditoria::class);
        $r=$s->executar();
        $this->assertFalse($r['politica_definida']);
        $this->assertSame([false,false,false,false],array_column($r['meses'],'fora_prazo_mes_completo'));
        $r=$s->executar('180');
        $this->assertTrue($r['simulacao']);
        $this->assertTrue($r['meses']['2026-01']['preservado']);
        $this->assertSame([false,true,false,false],array_column($r['meses'],'fora_prazo_mes_completo'));
        $this->assertDatabaseCount('auditoria_eventos',4);
        $this->assertStringNotContainsString('alunos',json_encode($r));
        $this->assertStringNotContainsString('referencias',json_encode($r));
    }
    public function test_restauracao_logica_reentrega_e_alteracao_com_volume_ficticio(): void
    {
        $backup=[];
        for ($i=1;$i<=1000;$i++) $backup[]=$this->evento(sprintf('00000000-0000-4000-8000-%012d',$i),$i%2 ? '2026-01' : '2026-02');
        foreach (array_chunk($backup,100) as $lote) DB::table('auditoria_eventos')->insert($lote);
        $s=app(ConferenciaAuditoria::class); $origem=$s->executar('180');
        // Apenas fixture descartável deste teste: simula restauração lógica em ordem inversa.
        DB::table('auditoria_eventos')->delete();
        foreach (array_chunk(array_reverse($backup),100) as $lote) DB::table('auditoria_eventos')->insert($lote);
        (new GravarAuditoria($backup[0]))->handle();
        $this->assertTrue($s->comparar($s->executar('90'),$origem));
        $this->assertDatabaseCount('auditoria_eventos',1000);
        DB::table('auditoria_eventos')->where('id',$backup[0]['id'])->update(['resultado'=>'erro']);
        $this->assertFalse($s->comparar($s->executar(),$origem));
        DB::table('auditoria_eventos')->where('id',$backup[0]['id'])->delete();
        $this->assertFalse($s->comparar($s->executar(),$origem));
        $this->assertFalse($s->comparar($origem,['versao'=>1,'meses'=>['2026-01'=>[]]]));
    }
    public function test_comando_compara_manifesto_e_rejeita_entrada_invalida_sem_escrita(): void
    {
        (new GravarAuditoria($this->evento('00000000-0000-4000-8000-000000000001','2026-01')))->handle();
        $s=app(ConferenciaAuditoria::class);
        $arquivo=tempnam(sys_get_temp_dir(),'au1-manifesto-');
        try {
            file_put_contents($arquivo,json_encode($s->executar()));
            $this->artisan('g360:conferir-auditoria',['--comparar'=>$arquivo])->assertSuccessful();
            file_put_contents($arquivo,'{"versao":1,"meses":{}}');
            $this->artisan('g360:conferir-auditoria',['--comparar'=>$arquivo])->assertFailed();
            $this->artisan('g360:conferir-auditoria',['--dias'=>'0'])->assertFailed();
            config(['auditoria.meses_preservados'=>['2026-99']]);
            $this->artisan('g360:conferir-auditoria')->assertFailed();
        } finally { unlink($arquivo); }
        $this->assertDatabaseCount('auditoria_eventos',1);
    }
    public function test_alerta_operacional_sobrevive_ao_filtro_sem_dados_livres(): void
    {
        $r=ProtegerDados::registro(new LogRecord(new \DateTimeImmutable(),'teste',Level::Error,
            'auditoria_enfileiramento_indisponivel',['token'=>'SEGREDO'],['email'=>'SEGREDO']));
        $this->assertSame('auditoria_enfileiramento_indisponivel',$r->message);
        $this->assertSame([],$r->context); $this->assertSame([],$r->extra);
    }
}
