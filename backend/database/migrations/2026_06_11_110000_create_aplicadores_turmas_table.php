<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aplicadores_turmas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('turma_id');
            $table->uuid('usuario_id');
            $table->string('papel', 30);
            $table->date('inicio_em');
            $table->date('fim_em')->nullable();
            $table->uuid('vinculado_por')->nullable();
            $table->timestamps(6);
            $table->foreign('turma_id')->references('id')->on('turmas')->restrictOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('vinculado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['usuario_id', 'turma_id', 'fim_em'], 'idx_aplicadores_turmas_usuario_ativo');
            $table->index(['turma_id', 'fim_em'], 'idx_aplicadores_turmas_turma_ativo');
        });

        DB::statement("ALTER TABLE aplicadores_turmas ADD CONSTRAINT ck_aplicadores_turmas_papel CHECK (papel IN ('professor', 'aplicador', 'responsavel'))");
        DB::statement('ALTER TABLE aplicadores_turmas ADD CONSTRAINT ck_aplicadores_turmas_periodo CHECK (fim_em IS NULL OR fim_em >= inicio_em)');
        DB::statement("ALTER TABLE aplicadores_turmas ADD chave_vigente VARCHAR(255) AS (IF(fim_em IS NULL, CONCAT(CAST(turma_id AS CHAR(36)), '|', CAST(usuario_id AS CHAR(36)), '|', papel), NULL)) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_aplicadores_turmas_ativo ON aplicadores_turmas (chave_vigente)');
    }

    public function down(): void
    {
        Schema::dropIfExists('aplicadores_turmas');
    }
};
