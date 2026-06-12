<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leituras_cartao', function (Blueprint $table) {
            $table->string('codigo_impresso_detectado', 120)->nullable()->after('operacao_id');
            $table->string('codigo_impresso_normalizado', 120)->nullable()->after('codigo_impresso_detectado');
            $table->string('codigo_sistema_proposto', 19)->nullable()->after('codigo_impresso_normalizado');
            $table->string('omr_versao', 80)->nullable()->after('status');
            $table->char('omr_configuracao_checksum', 64)->nullable()->after('omr_versao');
            $table->json('omr_metadados')->nullable()->after('omr_configuracao_checksum');
            $table->uuid('revisada_por_id')->nullable()->after('requer_revisao');
            $table->dateTime('revisada_at', 6)->nullable()->after('revisada_por_id');
            $table->text('motivo_revisao')->nullable()->after('revisada_at');
            $table->foreign('revisada_por_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['aplicacao_id', 'requer_revisao', 'status'], 'idx_leituras_aplicacao_revisao_status');
            $table->index(['codigo_impresso_normalizado', 'aplicacao_id'], 'idx_leituras_codigo_impresso_aplicacao');
        });
    }

    public function down(): void
    {
        Schema::table('leituras_cartao', function (Blueprint $table) {
            $table->dropForeign(['revisada_por_id']);
            $table->dropIndex('idx_leituras_aplicacao_revisao_status');
            $table->dropIndex('idx_leituras_codigo_impresso_aplicacao');
            $table->dropColumn([
                'codigo_impresso_detectado',
                'codigo_impresso_normalizado',
                'codigo_sistema_proposto',
                'omr_versao',
                'omr_configuracao_checksum',
                'omr_metadados',
                'revisada_por_id',
                'revisada_at',
                'motivo_revisao',
            ]);
        });
    }
};
