<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escolas', function (Blueprint $table) {
            $table->string('rede', 20)->nullable()->after('estado');
            $table->string('diretor', 180)->nullable()->after('email');
            $table->string('endereco_texto', 255)->nullable()->after('endereco');
        });

        DB::statement("ALTER TABLE escolas ADD CONSTRAINT ck_escolas_rede CHECK (rede IS NULL OR rede IN ('estadual', 'municipal', 'federal', 'privada'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE escolas DROP CONSTRAINT ck_escolas_rede');

        Schema::table('escolas', function (Blueprint $table) {
            $table->dropColumn(['rede', 'diretor', 'endereco_texto']);
        });
    }
};
