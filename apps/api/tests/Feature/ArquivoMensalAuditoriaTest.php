<?php
namespace Tests\Feature;

use App\Jobs\GravarAuditoria;
use App\Services\{ArquivoMensalAuditoria, ConferenciaAuditoria};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Crypt, DB, File};
use Tests\TestCase;

class ArquivoMensalAuditoriaTest extends TestCase
{
    use RefreshDatabase;
    private string $raiz;
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026,11,1));
        $this->raiz = sys_get_temp_dir().'/au1-arquivo-'.bin2hex(random_bytes(8));
        mkdir($this->raiz, 0700);
        config(['auditoria.meses_preservados'=>['2026-01']]);
    }
    protected function tearDown(): void
    {
        File::deleteDirectory($this->raiz);
        parent::tearDown();
    }
    private function evento(int $n, string $mes = '2026-01'): array
    {
        return ['id'=>sprintf('00000000-0000-4000-8000-%012d',$n), 'mes'=>$mes,
            'ocorrido_em'=>$mes.'-10 12:00:00.123456', 'metodo'=>'GET', 'rota'=>'api/v1/alunos/{id}',
            'referencias'=>'{"id":"123"}', 'status_http'=>200, 'resultado'=>'sucesso'];
    }
    public function test_copia_cifrada_confere_com_inventario_sem_excluir_mes_preservado(): void
    {
        foreach ([3,1,2] as $n) (new GravarAuditoria($this->evento($n)))->handle();
        (new GravarAuditoria($this->evento(4,'2026-02')))->handle();
        $s = app(ArquivoMensalAuditoria::class);
        $r = $s->criar('2026-01',$this->raiz.'/primeira');
        $inventario = app(ConferenciaAuditoria::class)->executar();
        $this->assertSame($inventario['meses']['2026-01']['sha256'],$r['sha256']);
        $this->assertSame(3,$r['quantidade']);
        $this->assertSame($r,$s->verificar('2026-01',$this->raiz.'/primeira'));
        $this->assertStringNotContainsString('alunos',file_get_contents($this->raiz.'/primeira/eventos.enc'));
        $this->assertDatabaseCount('auditoria_eventos',4);
        $this->assertTrue($inventario['meses']['2026-01']['preservado']);
        $this->artisan('g360:arquivar-auditoria',['mes'=>'2026-01','destino'=>$this->raiz.'/primeira'])->assertFailed();
        // Evento tardio exige revisão nova; replay com outro mês não contorna UUID global.
        (new GravarAuditoria($this->evento(1,'2026-02')))->handle();
        (new GravarAuditoria($this->evento(5)))->handle();
        $novo = $s->criar('2026-01',$this->raiz.'/segunda');
        $this->assertSame(4,$novo['quantidade']);
        $this->assertNotSame($novo['sha256'],$r['sha256']);
        $this->assertSame($r,$s->verificar('2026-01',$this->raiz.'/primeira'));
    }
    public function test_truncamento_reordenacao_chave_errada_e_manifesto_alterado_falham(): void
    {
        foreach ([1,2] as $n) (new GravarAuditoria($this->evento($n)))->handle();
        $s = app(ArquivoMensalAuditoria::class); $dir = $this->raiz.'/copia';
        $s->criar('2026-01',$dir);
        $original = file_get_contents($dir.'/eventos.enc');
        foreach ([substr($original,0,-10), implode("\n",array_reverse(explode("\n",trim($original))))."\n"] as $alterado) {
            file_put_contents($dir.'/eventos.enc',$alterado);
            $this->artisan('g360:arquivar-auditoria',['mes'=>'2026-01','destino'=>$dir,'--verificar'=>true])->assertFailed();
        }
        file_put_contents($dir.'/eventos.enc',$original);
        $cifrador = Crypt::getFacadeRoot();
        try {
            Crypt::swap(new \Illuminate\Encryption\Encrypter(random_bytes(32),'AES-256-CBC'));
            $this->artisan('g360:arquivar-auditoria',['mes'=>'2026-01','destino'=>$dir,'--verificar'=>true])->assertFailed();
        } finally { Crypt::swap($cifrador); }
        $m = json_decode(file_get_contents($dir.'/manifesto.json'),true); $m['quantidade']++;
        file_put_contents($dir.'/manifesto.json',json_encode($m));
        $this->artisan('g360:arquivar-auditoria',['mes'=>'2026-01','destino'=>$dir,'--verificar'=>true])->assertFailed();
        $this->assertDatabaseCount('auditoria_eventos',2);
    }
    public function test_recusa_mes_aberto_e_inconsistencia_e_verifica_copia_sem_banco(): void
    {
        foreach (['2026-11','2027-01','../2026-01','2026-99'] as $mes) {
            $this->artisan('g360:arquivar-auditoria',['mes'=>$mes,'destino'=>$this->raiz.'/invalido'])->assertFailed();
        }
        (new GravarAuditoria($this->evento(1)))->handle();
        $s = app(ArquivoMensalAuditoria::class); $dir = $this->raiz.'/valido';
        $s->criar('2026-01',$dir);
        $consultas = 0;
        DB::listen(function () use (&$consultas) { $consultas++; });
        $this->assertSame(1,$s->verificar('2026-01',$dir)['quantidade']);
        $this->assertSame(0,$consultas);
        DB::table('auditoria_eventos')->update(['ocorrido_em'=>'2026-02-01 12:00:00']);
        $this->artisan('g360:arquivar-auditoria',['mes'=>'2026-01','destino'=>$this->raiz.'/inconsistente'])->assertFailed();
        $this->assertFileDoesNotExist($this->raiz.'/inconsistente/manifesto.json');
    }
}
