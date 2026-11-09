<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\{Escopo, RelogioConsulta};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class F2RelogioConsultaTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Support\CenarioDisciplinas { setUp as private iniciarCenario; }

    protected function setUp(): void { $this->iniciarCenario(); }

    public function test_relogio_sql_acompanha_travel_e_reset_no_mesmo_pdo(): void
    {
        $pdo = DB::connection()->getPdo();
        $ler = fn () => DB::query()->selectRaw((string) RelogioConsulta::agora()->getValue(DB::connection()->getQueryGrammar()).' as instante')->first()->instante;
        $this->travelTo(now('UTC')->setDate(2026, 11, 4)->setTime(2, 59, 59));
        $this->assertSame('2026-11-04 02:59:59', $ler());
        $this->travel(1)->seconds();
        $this->assertSame('2026-11-04 03:00:00', $ler());
        $this->assertSame($pdo, DB::connection()->getPdo());
        $this->travelBack();
        $instante = \Carbon\CarbonImmutable::parse($ler(), 'UTC')->timestamp;
        $this->assertLessThanOrEqual(3, abs(now('UTC')->timestamp - $instante));
    }

    public function test_builder_compilado_expira_com_relogio_real_sem_callback_de_autorizacao(): void
    {
        $this->travelBack();
        $u = Usuario::findOrFail(2)->noVinculo(3);
        $q = Escopo::turmas($u);
        $q->toSql();
        $this->assertSame([1], $q->pluck('turmas.id')->all());
        $ler = fn () => DB::query()->selectRaw((string) RelogioConsulta::agora()->getValue(DB::connection()->getQueryGrammar()).' as instante')->first()->instante;
        $fim = \Carbon\CarbonImmutable::parse($ler(), 'UTC')->addSecond();
        DB::table('usuario_vinculos')->where('id', 3)->update(['fim_em' => $fim]);
        // Aguarda uma transição de estado, não mede latência nem impõe orçamento de desempenho.
        $limite = hrtime(true) + 10_000_000_000;
        while (strcmp($ler(), $fim->format('Y-m-d H:i:s')) < 0 && hrtime(true) < $limite) usleep(50_000);
        $this->assertGreaterThanOrEqual($fim->format('Y-m-d H:i:s'), $ler());
        $this->assertSame([], $q->pluck('turmas.id')->all());
        $this->assertFalse($q->exists());
    }

    public function test_harness_reconecta_conexao_desligada_antes_de_sincronizar_o_relogio(): void
    {
        config(['database.connections.f2_clock_reconnect' => config('database.connections.'.config('database.default'))]);
        $c = DB::connection('f2_clock_reconnect');
        $this->travelTo(now('UTC')->setDate(2026, 11, 4)->setTime(12, 0, 0));
        $sql = $c->getDriverName() === 'sqlite' ? 'SELECT CURRENT_TIMESTAMP AS instante' : 'SELECT UTC_TIMESTAMP() AS instante';
        try {
            $this->assertSame('2026-11-04 12:00:00', $c->selectOne($sql)->instante);
            $antigo = $c->getPdo();
            $c->disconnect();
            $this->assertNull($c->getRawPdo());
            $this->travel(1)->seconds();
            $this->assertSame('2026-11-04 12:00:01', $c->selectOne($sql)->instante);
            $this->assertNotSame($antigo, $c->getPdo());
        } finally { DB::purge('f2_clock_reconnect'); }
    }
}
