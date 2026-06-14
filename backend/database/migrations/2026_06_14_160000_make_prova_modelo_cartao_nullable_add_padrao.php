<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provas', function (Blueprint $table) {
            $table->dropForeign(['modelo_cartao_id']);
        });

        DB::statement('ALTER TABLE provas MODIFY modelo_cartao_id CHAR(36) NULL');

        Schema::table('provas', function (Blueprint $table) {
            $table->foreign('modelo_cartao_id')->references('id')->on('modelos_cartao')->restrictOnDelete();
            $table->json('padrao')->nullable()->after('alternativas');
        });
    }

    public function down(): void
    {
        Schema::table('provas', function (Blueprint $table) {
            $table->dropForeign(['modelo_cartao_id']);
            $table->dropColumn('padrao');
        });

        DB::statement('ALTER TABLE provas MODIFY modelo_cartao_id CHAR(36) NOT NULL');

        Schema::table('provas', function (Blueprint $table) {
            $table->foreign('modelo_cartao_id')->references('id')->on('modelos_cartao')->restrictOnDelete();
        });
    }
};
