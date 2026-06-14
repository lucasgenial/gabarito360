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

        $this->modifyModeloCartaoColumn('NULL');

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

        $this->modifyModeloCartaoColumn('NOT NULL');

        Schema::table('provas', function (Blueprint $table) {
            $table->foreign('modelo_cartao_id')->references('id')->on('modelos_cartao')->restrictOnDelete();
        });
    }

    /**
     * Altera a nulabilidade de provas.modelo_cartao_id preservando o charset e
     * a collation EXATOS de modelos_cartao.id, para que a FK forme em qualquer
     * configuração de servidor (a FK exige collations idênticas).
     */
    private function modifyModeloCartaoColumn(string $nullability): void
    {
        $column = DB::selectOne(
            'SELECT character_set_name AS charset, collation_name AS collation
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND column_name = ?',
            ['modelos_cartao', 'id'],
        );

        DB::statement(sprintf(
            'ALTER TABLE provas MODIFY modelo_cartao_id CHAR(36) CHARACTER SET %s COLLATE %s %s',
            $column->charset,
            $column->collation,
            $nullability,
        ));
    }
};
