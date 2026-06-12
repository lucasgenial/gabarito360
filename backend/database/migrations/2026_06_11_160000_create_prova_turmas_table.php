<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prova_turmas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prova_id');
            $table->uuid('turma_id');
            $table->date('data_prevista')->nullable();
            $table->uuid('vinculado_por')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->foreign('prova_id')->references('id')->on('provas')->restrictOnDelete();
            $table->foreign('turma_id')->references('id')->on('turmas')->restrictOnDelete();
            $table->foreign('vinculado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->unique(['prova_id', 'turma_id'], 'uq_prova_turmas_prova_turma');
            $table->index(['turma_id', 'data_prevista'], 'idx_prova_turmas_turma_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prova_turmas');
    }
};
