<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_id')->unique()->constrained('cartoes')->cascadeOnDelete();
            $table->foreignId('prova_id')->constrained('provas')->cascadeOnDelete();
            $table->foreignId('aluno_id')->constrained('alunos')->cascadeOnDelete();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->tinyInteger('acertos')->unsigned();
            $table->tinyInteger('total_questoes')->unsigned();
            $table->decimal('nota_final', 5, 2);
            $table->enum('status_aprovacao', ['aprovado', 'recuperacao']);
            $table->json('acertos_por_tema')->nullable();
            $table->timestamps();

            $table->index('prova_id');
            $table->index('aluno_id');
            $table->index('turma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
