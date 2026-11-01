<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlanejamentoConsultasMariaDbTest extends TestCase
{
    public function test_limite_persiste_ao_reconectar_sem_alterar_servidor(): void
    {
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Exige conexão MySQL/MariaDB real.');
        }
        $global = DB::selectOne('SELECT @@GLOBAL.optimizer_search_depth AS valor')->valor;
        $base = config('database.connections.'.config('database.default'));
        foreach (['mysql', 'mariadb'] as $driver) {
            $nome = 'planejamento_'.$driver;
            config(['database.connections.'.$nome => array_replace($base, [
                'driver' => $driver,
                'options' => config('database.connections.'.$driver.'.options'),
            ])]);
            try {
                $this->assertSame(4, (int) DB::connection($nome)->selectOne('SELECT @@SESSION.optimizer_search_depth AS valor')->valor);
                DB::connection($nome)->statement('SET SESSION optimizer_search_depth=62');
                DB::purge($nome);
                $this->assertSame(4, (int) DB::connection($nome)->selectOne('SELECT @@SESSION.optimizer_search_depth AS valor')->valor);
                $this->assertSame((int) $global, (int) DB::connection($nome)->selectOne('SELECT @@GLOBAL.optimizer_search_depth AS valor')->valor);
            } finally {
                DB::purge($nome);
            }
        }
    }
}
