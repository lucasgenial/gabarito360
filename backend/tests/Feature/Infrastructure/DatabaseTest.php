<?php

namespace Tests\Feature\Infrastructure;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    public function test_testing_database_is_isolated_mariadb_with_required_capabilities(): void
    {
        $connectionName = config('database.default');
        $connectionConfig = config("database.connections.{$connectionName}");
        $databaseName = $connectionConfig['database'] ?? null;

        $this->assertSame('mariadb_testing', $connectionName);
        $this->assertSame('mariadb', $connectionConfig['driver'] ?? null);
        $this->assertIsString($databaseName);
        $this->assertMatchesRegularExpression(
            '/(?:^|[_-])test(?:ing)?(?:[_-]|$)/i',
            $databaseName,
            'The automated test database must be isolated and explicitly named as a test database.',
        );
        $this->assertTrue(extension_loaded('pdo_mysql'), 'The pdo_mysql extension is required.');

        $connection = DB::connection();
        $this->assertSame('mariadb', $connection->getDriverName());
        $this->assertSame($databaseName, $connection->getDatabaseName());
        $this->assertSame($databaseName, $connection->selectOne('SELECT DATABASE() AS name')->name);

        $capabilities = $connection->selectOne(
            'SELECT VERSION() AS version, @@character_set_database AS charset_name, @@collation_database AS collation_name'
        );

        $this->assertStringContainsString('MariaDB', $capabilities->version);
        $this->assertSame('utf8mb4', $capabilities->charset_name);
        $this->assertSame('utf8mb4_unicode_ci', $capabilities->collation_name);
        $this->assertSame('uuid', config('database.conventions.primary_key_type'));
        $this->assertSame('application', config('database.conventions.uuid_generation'));
        $this->assertSame('datetime(6) UTC', config('database.conventions.timestamp_type'));
    }
}
