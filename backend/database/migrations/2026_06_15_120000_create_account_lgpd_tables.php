<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelas secundárias de B8 (conta e LGPD):
 *
 * - `planos_uso`: plano contratado, limites e uso por núcleo;
 * - `politicas_retencao`: prazos e base legal de retenção por tipo de dado;
 * - `execucoes_descarte`: trilha de anonimização/inativação/descarte executados.
 *
 * Também adiciona `titular_id` a `solicitacoes_lgpd` para tornar o processamento
 * rastreável (o hash do titular permanece para buscas anônimas). As demais
 * tabelas de B8 (`preferencias_usuario`, `solicitacoes_lgpd`, `consentimentos`,
 * `auditorias`) já existem desde as fundações.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos_uso', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('nucleo_id');
            $table->string('plano', 40)->default('institucional');
            $table->json('limites');
            $table->json('uso');
            $table->date('ciclo_inicio')->nullable();
            $table->date('ciclo_fim')->nullable();
            $table->dateTime('atualizado_em', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->cascadeOnDelete();
            $table->unique('nucleo_id', 'uq_planos_uso_nucleo');
        });

        Schema::create('politicas_retencao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entidade_tipo', 80);
            $table->string('descricao', 255);
            $table->unsignedInteger('reter_dias');
            $table->string('base_legal', 120)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps(6);
            $table->unique('entidade_tipo', 'uq_politicas_retencao_entidade');
        });

        Schema::create('execucoes_descarte', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('politica_retencao_id')->nullable();
            $table->uuid('solicitacao_lgpd_id')->nullable();
            $table->string('entidade_tipo', 80);
            $table->string('acao', 30);
            $table->unsignedInteger('afetados')->default(0);
            $table->json('detalhes')->nullable();
            $table->uuid('executado_por_id')->nullable();
            $table->dateTime('executado_at', 6)->useCurrent();
            $table->timestamps(6);
            $table->foreign('politica_retencao_id')->references('id')->on('politicas_retencao')->nullOnDelete();
            $table->foreign('solicitacao_lgpd_id')->references('id')->on('solicitacoes_lgpd')->nullOnDelete();
            $table->foreign('executado_por_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['entidade_tipo', 'executado_at'], 'idx_execucoes_descarte_entidade_data');
            $table->index(['solicitacao_lgpd_id'], 'idx_execucoes_descarte_solicitacao');
        });

        Schema::table('solicitacoes_lgpd', function (Blueprint $table) {
            $table->uuid('titular_id')->nullable()->after('titular_referencia_hash');
            $table->index(['titular_id'], 'idx_solicitacoes_lgpd_titular_id');
        });

        DB::statement("ALTER TABLE execucoes_descarte ADD CONSTRAINT ck_execucoes_descarte_acao CHECK (acao IN ('anonimizacao', 'inativacao', 'descarte'))");
    }

    public function down(): void
    {
        Schema::table('solicitacoes_lgpd', function (Blueprint $table) {
            $table->dropIndex('idx_solicitacoes_lgpd_titular_id');
            $table->dropColumn('titular_id');
        });
        Schema::dropIfExists('execucoes_descarte');
        Schema::dropIfExists('politicas_retencao');
        Schema::dropIfExists('planos_uso');
    }
};
