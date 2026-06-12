<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mariadb') {
            throw new LogicException('Gabarito360 migrations require MariaDB.');
        }
    }

    public function down(): void {}
};
