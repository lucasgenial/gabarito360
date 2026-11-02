<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('g360:permissoes-sincronizar');
    }

    public function down(): void
    {
        throw new RuntimeException('T5: desativar pela flag; preservar ajustes de permissão dos vínculos.');
    }
};
