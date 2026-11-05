<?php
namespace Tests\Support;

use Illuminate\Foundation\Testing\{DatabaseMigrations, RefreshDatabaseState};
use Illuminate\Support\Facades\DB;

/** Transações HTTP reais; o histórico não admite migrate:rollback, nem mesmo nos testes. */
trait BancoCurriculoDescartavel
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        if (in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true)
            && !str_ends_with(DB::connection()->getDatabaseName(),'_test')) {
            throw new \RuntimeException('Use banco descartável _test.');
        }
        RefreshDatabaseState::$migrated=false;
        $this->artisan('migrate:fresh',['--force'=>true])->assertSuccessful();
        // O próximo teste reconstrói somente o banco descartável, sem apagar por down().
        $this->beforeApplicationDestroyed(static function(){RefreshDatabaseState::$migrated=false;});
    }
}
