<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importacoes_alunos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('escola_id');
            $table->uuid('turma_id');
            $table->uuid('solicitado_por')->nullable();
            $table->uuid('confirmado_por')->nullable();
            $table->string('status', 30);
            $table->string('arquivo_disco', 40);
            $table->string('arquivo_caminho', 500);
            $table->string('arquivo_nome', 255);
            $table->char('arquivo_checksum_sha256', 64);
            $table->json('resumo');
            $table->json('erros');
            $table->dateTime('confirmado_at', 6)->nullable();
            $table->dateTime('processado_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->foreign('turma_id')->references('id')->on('turmas')->restrictOnDelete();
            $table->foreign('solicitado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('confirmado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['escola_id', 'created_at'], 'idx_importacoes_alunos_escola_created');
            $table->index(['solicitado_por', 'created_at'], 'idx_importacoes_alunos_solicitante_created');
            $table->index(['status', 'confirmado_at'], 'idx_importacoes_alunos_processando');
        });

        DB::statement("ALTER TABLE importacoes_alunos ADD CONSTRAINT ck_importacoes_alunos_status CHECK (status IN ('validada', 'com_erros', 'processando', 'concluida', 'falhou'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('importacoes_alunos');
    }
};
