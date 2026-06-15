<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provas', function (Blueprint $table) {
            $table->dropForeign(['modelo_cartao_id']);
        });

        // Reusa o mesmo Blueprint uuid() que criou modelos_cartao.id, garantindo
        // tipo/charset/collation IDÊNTICOS aos da coluna referenciada em qualquer
        // servidor — no MariaDB 11.4 (CI) uuid() é o tipo nativo UUID (sem
        // collation); em versões mais antigas é char(36). Forçar CHAR(36) aqui
        // quebrava a FK no CI (errno 150). Com ->change() a FK forma por construção.
        Schema::table('provas', function (Blueprint $table) {
            $table->uuid('modelo_cartao_id')->nullable()->change();
        });

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

        Schema::table('provas', function (Blueprint $table) {
            $table->uuid('modelo_cartao_id')->nullable(false)->change();
        });

        Schema::table('provas', function (Blueprint $table) {
            $table->foreign('modelo_cartao_id')->references('id')->on('modelos_cartao')->restrictOnDelete();
        });
    }
};
