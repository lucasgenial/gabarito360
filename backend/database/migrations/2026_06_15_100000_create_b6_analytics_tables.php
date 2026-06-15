<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelas secundárias de B6 (resultados/dashboards/relatórios):
 *
 * - `snapshots_indicadores`: fotografia de KPIs por escopo (aplicação, prova,
 *   escola, núcleo) para alimentar dashboards e comparações históricas;
 * - `exportacoes`: artefatos de exportação (csv/pdf/xlsx) de relatórios de
 *   prova/turma, autorizados e auditados;
 * - `comparativos`: comparações persistidas entre entidades (escolas de um
 *   núcleo, turmas de uma prova).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snapshots_indicadores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('escopo_tipo', 30);
            $table->uuid('escopo_id');
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->uuid('prova_id')->nullable();
            $table->unsignedInteger('total_resultados')->default(0);
            $table->decimal('media_nota', 7, 4)->nullable();
            $table->json('indicadores');
            $table->uuid('gerado_por_id')->nullable();
            $table->dateTime('gerado_at', 6)->useCurrent();
            $table->timestamps(6);
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->nullOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->nullOnDelete();
            $table->foreign('prova_id')->references('id')->on('provas')->nullOnDelete();
            $table->foreign('gerado_por_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['escopo_tipo', 'escopo_id', 'gerado_at'], 'idx_snapshots_escopo_data');
            $table->index(['prova_id', 'gerado_at'], 'idx_snapshots_prova_data');
        });

        Schema::create('exportacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo', 50);
            $table->string('formato', 10);
            $table->string('status', 30)->default('processando');
            $table->uuid('solicitante_id');
            $table->uuid('prova_id')->nullable();
            $table->uuid('turma_id')->nullable();
            $table->uuid('arquivo_id')->nullable();
            $table->json('filtros');
            $table->json('escopo');
            $table->unsignedInteger('linhas')->default(0);
            $table->dateTime('solicitado_at', 6)->useCurrent();
            $table->dateTime('concluido_at', 6)->nullable();
            $table->dateTime('expira_at', 6)->nullable();
            $table->string('erro_codigo', 80)->nullable();
            $table->timestamps(6);
            $table->foreign('solicitante_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('prova_id')->references('id')->on('provas')->nullOnDelete();
            $table->foreign('turma_id')->references('id')->on('turmas')->nullOnDelete();
            $table->foreign('arquivo_id')->references('id')->on('arquivos')->nullOnDelete();
            $table->index(['solicitante_id', 'status', 'solicitado_at'], 'idx_exportacoes_solicitante_status_data');
            $table->index(['prova_id', 'formato'], 'idx_exportacoes_prova_formato');
            $table->index(['status', 'expira_at'], 'idx_exportacoes_status_expiracao');
        });

        Schema::create('comparativos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo', 50);
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->uuid('prova_id')->nullable();
            $table->json('parametros');
            $table->json('resultado');
            $table->uuid('gerado_por_id')->nullable();
            $table->dateTime('gerado_at', 6)->useCurrent();
            $table->timestamps(6);
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->nullOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->nullOnDelete();
            $table->foreign('prova_id')->references('id')->on('provas')->nullOnDelete();
            $table->foreign('gerado_por_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['tipo', 'nucleo_id'], 'idx_comparativos_tipo_nucleo');
            $table->index(['prova_id', 'gerado_at'], 'idx_comparativos_prova_data');
        });

        DB::statement("ALTER TABLE snapshots_indicadores ADD CONSTRAINT ck_snapshots_escopo CHECK (escopo_tipo IN ('aplicacao', 'prova', 'escola', 'nucleo'))");
        DB::statement("ALTER TABLE exportacoes ADD CONSTRAINT ck_exportacoes_formato CHECK (formato IN ('csv', 'pdf', 'xlsx'))");
        DB::statement("ALTER TABLE exportacoes ADD CONSTRAINT ck_exportacoes_status CHECK (status IN ('processando', 'concluido', 'falhou', 'expirado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('comparativos');
        Schema::dropIfExists('exportacoes');
        Schema::dropIfExists('snapshots_indicadores');
    }
};
