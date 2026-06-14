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
     * a collation da conexão (os mesmos usados para criar as colunas UUID), para
     * que a FK com modelos_cartao.id forme em qualquer servidor — a FK exige
     * collations idênticas.
     */
    private function modifyModeloCartaoColumn(string $nullability): void
    {
        $connection = Schema::getConnection();
        $charset = $connection->getConfig('charset') ?: 'utf8mb4';
        $collation = $connection->getConfig('collation') ?: 'utf8mb4_unicode_ci';

        $connection->statement(sprintf(
            'ALTER TABLE provas MODIFY modelo_cartao_id CHAR(36) CHARACTER SET %s COLLATE %s %s',
            $charset,
            $collation,
            $nullability,
        ));
    }
};
