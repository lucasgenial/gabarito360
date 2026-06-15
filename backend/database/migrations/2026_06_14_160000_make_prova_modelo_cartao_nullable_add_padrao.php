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
     * Altera a nulabilidade de provas.modelo_cartao_id casando EXATAMENTE a
     * collation de modelos_cartao.id (lida do servidor em tempo de migration).
     * A FK exige collations idênticas; o default de collation do servidor varia
     * entre versões do MariaDB (ex.: 11.4 no CI vs. local), então não dá para
     * fixar nem confiar no default da conexão.
     */
    private function modifyModeloCartaoColumn(string $nullability): void
    {
        $collation = $this->referencedIdCollation();

        // COLLATE sem CHARACTER SET: o charset é inferido da própria collation.
        Schema::getConnection()->statement(sprintf(
            'ALTER TABLE provas MODIFY modelo_cartao_id CHAR(36) COLLATE %s %s',
            $collation,
            $nullability,
        ));
    }

    /**
     * Lê a collation real da coluna id de modelos_cartao via SHOW FULL COLUMNS
     * (resolvido no schema atual da conexão — não depende de DATABASE()).
     */
    private function referencedIdCollation(): string
    {
        foreach (Schema::getConnection()->select('SHOW FULL COLUMNS FROM modelos_cartao') as $column) {
            $column = (array) $column;

            if (($column['Field'] ?? null) === 'id') {
                $collation = $column['Collation'] ?? null;

                if (is_string($collation) && $collation !== '') {
                    return $collation;
                }
            }
        }

        throw new \RuntimeException('Não foi possível determinar a collation de modelos_cartao.id.');
    }
};
