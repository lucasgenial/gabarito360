<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escola_id')->constrained('escolas')->cascadeOnDelete();
            $table->string('nome', 50);
            $table->string('serie', 50);
            $table->enum('turno', ['manha', 'tarde', 'noite', 'integral']);
            $table->year('ano_letivo');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('escola_id');
            $table->index(['escola_id', 'ano_letivo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turmas');
    }
};
