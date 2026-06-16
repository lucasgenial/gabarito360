<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_escopos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->enum('escopo_tipo', ['rede', 'nucleo', 'escola', 'turma', 'aluno']);
            $table->unsignedBigInteger('escopo_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['usuario_id', 'escopo_tipo']);
            $table->index(['escopo_tipo', 'escopo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_escopos');
    }
};
