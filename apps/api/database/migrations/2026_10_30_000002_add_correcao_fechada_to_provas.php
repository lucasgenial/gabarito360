<?php

/**
 * Arquivo: apps/api/database/migrations/2026_10_30_000002_add_correcao_fechada_to_provas.php
 * Função: Correção aberta/fechada. Fechada, só a supervisão (coordenador, diretor, admin)
 *         altera, exclui ou inclui correções — ou reabre para o professor/corretor.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provas', function (Blueprint $table) {
            $table->timestamp('correcao_fechada_em')->nullable()->after('status');
            $table->foreignId('correcao_fechada_por')->nullable()->after('correcao_fechada_em')
                ->constrained('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('provas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('correcao_fechada_por');
            $table->dropColumn('correcao_fechada_em');
        });
    }
};
