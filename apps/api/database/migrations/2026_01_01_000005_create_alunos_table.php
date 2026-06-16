<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alunos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->string('nome', 200);
            $table->string('matricula', 20)->unique();
            $table->date('data_nascimento')->nullable();
            $table->string('nome_responsavel', 200)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('turma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alunos');
    }
};
