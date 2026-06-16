<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prova_turmas', function (Blueprint $table) {
            $table->foreignId('prova_id')->constrained('provas')->cascadeOnDelete();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();

            $table->primary(['prova_id', 'turma_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prova_turmas');
    }
};
