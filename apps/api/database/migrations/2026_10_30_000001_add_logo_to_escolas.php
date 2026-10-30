<?php

/**
 * Arquivo: apps/api/database/migrations/2026_10_30_000001_add_logo_to_escolas.php
 * Função: Logo da escola, usado no cabeçalho dos relatórios impressos e PDFs.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escolas', function (Blueprint $table) {
            $table->string('logo_path', 255)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('escolas', fn (Blueprint $t) => $t->dropColumn('logo_path'));
    }
};
