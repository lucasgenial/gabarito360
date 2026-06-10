<?php

namespace Tests\Feature\Infrastructure;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    public function test_testing_database_is_isolated_postgresql_with_required_capabilities(): void
    {
        $connectionName = config('database.default');
        $connectionConfig = config("database.connections.{$connectionName}");
        $databaseName = $connectionConfig['database'] ?? null;

        $this->assertSame('pgsql_testing', $connectionName);
        $this->assertSame('pgsql', $connectionConfig['driver'] ?? null);
        $this->assertIsString($databaseName);
        $this->assertMatchesRegularExpression(
            '/(?:^|[_-])test(?:ing)?(?:[_-]|$)/i',
            $databaseName,
            'The automated test database must be isolated and explicitly named as a test database.',
        );
        $this->assertTrue(
            extension_loaded('pdo_pgsql'),
            'The pdo_pgsql PHP extension is required for PostgreSQL integration tests.',
        );

        $connection = DB::connection();
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame($databaseName, $connection->getDatabaseName());
        $this->assertSame(
            $databaseName,
            $connection->selectOne('SELECT current_database() AS name')->name,
        );

        $extensions = collect($connection->select(
            "SELECT extname FROM pg_extension WHERE extname IN ('pgcrypto', 'citext') ORDER BY extname"
        ))->pluck('extname')->all();

        $this->assertSame(['citext', 'pgcrypto'], $extensions);

        $capabilities = $connection->selectOne(
            "SELECT gen_random_uuid()::text AS uuid, ('GABARITO360@EXAMPLE.COM'::citext = 'gabarito360@example.com'::citext) AS citext_case_insensitive"
        );

        $this->assertTrue(Str::isUuid($capabilities->uuid, 4));
        $this->assertTrue((bool) $capabilities->citext_case_insensitive);
        $this->assertSame('uuid', config('database.conventions.primary_key_type'));
        $this->assertSame('gen_random_uuid()', config('database.conventions.uuid_default_expression'));
        $this->assertSame('timestamptz', config('database.conventions.timestamp_type'));
    }
}
