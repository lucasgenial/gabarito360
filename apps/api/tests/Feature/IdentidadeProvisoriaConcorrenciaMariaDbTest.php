<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class IdentidadeProvisoriaConcorrenciaMariaDbTest extends TestCase
{
    public function test_dois_executores_criam_uma_unica_referencia_sem_pessoas_orfas(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) $this->markTestSkipped('Concorrência exige MariaDB descartável.');
        if (!str_ends_with(DB::connection()->getDatabaseName(), '_test')) throw new \RuntimeException('Use banco descartável terminado em _test.');
        \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        $u = Usuario::create(['nome' => 'Pessoa fictícia', 'perfil' => 'professor', 'email' => 'race-id1@example.test', 'cpf' => '00000000001', 'password' => 'ficticio']);
        $antes = DB::table('usuarios')->where('id', $u->id)->first();
        $db = config('database.connections.'.config('database.default'));
        $env = ['APP_ENV' => 'testing', 'APP_KEY' => config('app.key'), 'DB_URL' => '', 'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => $db['host'], 'DB_PORT' => (string) $db['port'], 'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'], 'DB_PASSWORD' => $db['password'], 'DB_SOCKET' => $db['unix_socket'] ?? ''];
        $workers = []; DB::beginTransaction(); DB::table('usuarios')->where('id', $u->id)->lockForUpdate()->first();
        try {
            foreach ([1, 2] as $i) {
                $w = new Process([PHP_BINARY, base_path('tests/Support/identidade-provisoria-worker.php')], base_path(), $env, null, 20);
                $w->start(); $workers[] = $w;
            }
            foreach ($workers as $w) {
                $fim = microtime(true) + 5;
                while (!str_contains($w->getOutput(), 'READY') && $w->isRunning() && microtime(true) < $fim) usleep(20000);
                $this->assertStringContainsString('READY', $w->getOutput());
            }
            $this->assertDatabaseCount('pessoas', 0); DB::commit();
            $criados = 0;
            foreach ($workers as $w) {
                $w->wait(); $this->assertTrue($w->isSuccessful(), 'Executor de teste deve terminar sem erro.');
                $r = json_decode(trim(str_replace("READY\n", '', $w->getOutput())), true, 512, JSON_THROW_ON_ERROR);
                $criados += $r['criados'];
            }
            $this->assertSame(1, $criados);
            $this->assertDatabaseCount('pessoas', 1); $this->assertDatabaseCount('pessoa_usuarios', 1);
            $this->assertDatabaseCount('pessoa_identidade_eventos', 1);
            $this->assertSame((array) $antes, (array) DB::table('usuarios')->where('id', $u->id)->first());
        } finally {
            while (DB::transactionLevel()) DB::rollBack();
            foreach ($workers as $w) if ($w->isRunning()) $w->stop();
        }
    }
}
