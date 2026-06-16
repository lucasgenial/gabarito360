<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prova_id')->constrained('provas')->cascadeOnDelete();
            $table->foreignId('aluno_id')->nullable()->constrained('alunos')->nullOnDelete();
            $table->string('imagem_url', 500)->nullable();
            $table->enum('status', ['pendente', 'lido', 'ambiguo'])->default('pendente');
            $table->decimal('confianca_geral', 5, 4)->nullable();
            $table->foreignId('resolvido_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('resolvido_em')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('revisado_em')->nullable();
            $table->timestamps();

            $table->index('prova_id');
            $table->index('aluno_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartoes');
    }
};
