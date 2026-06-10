<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            throw new LogicException('Gabarito360 migrations require PostgreSQL.');
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');
    }

    public function down(): void
    {
        // Extensions are database capabilities and may be shared by other schemas.
    }
};
