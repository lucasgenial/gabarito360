<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartao_envios', function (Blueprint $table) {
            $table->id();
            $table->uuid('chave');
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('prova_id')->constrained('provas')->cascadeOnDelete();
            $table->foreignId('cartao_id')->nullable()->constrained('cartoes')->nullOnDelete();
            $table->string('status', 30)->default('na_fila');
            $table->unsignedInteger('tentativa')->default(1);
            $table->string('mensagem', 500)->nullable();
            $table->json('resultado')->nullable();
            $table->timestamps();
            $table->unique(['usuario_id', 'chave']);
            $table->index(['prova_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartao_envios');
    }
};
