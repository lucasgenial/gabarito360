<?php

/**
 * Arquivo: apps/api/database/migrations/2026_10_29_000001_add_opcao_lingua_e_identificacao_omr.php
 * Função: Suporte a provas com opção de língua estrangeira (questões 01–05 com
 *         gabarito Inglês/Espanhol, padrão ENEM) e registro da língua lida no cartão.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provas', function (Blueprint $table) {
            $table->boolean('opcao_lingua')->default(false)->after('anular_se_todas');
        });

        Schema::table('gabarito_questoes', function (Blueprint $table) {
            // Gabarito da opção Espanhol; `alternativa` guarda a opção Inglês.
            $table->char('alternativa_espanhol', 1)->nullable()->after('alternativa');
        });

        Schema::table('cartoes', function (Blueprint $table) {
            $table->char('lingua', 3)->nullable()->after('aluno_id');
        });
    }

    public function down(): void
    {
        Schema::table('cartoes', fn (Blueprint $t) => $t->dropColumn('lingua'));
        Schema::table('gabarito_questoes', fn (Blueprint $t) => $t->dropColumn('alternativa_espanhol'));
        Schema::table('provas', fn (Blueprint $t) => $t->dropColumn('opcao_lingua'));
    }
};
