<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambiguidade_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_id')->constrained('cartoes')->cascadeOnDelete();
            $table->foreignId('cartao_resposta_id')->constrained('cartao_respostas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->char('alternativa_escolhida', 1);
            $table->timestamp('created_at')->useCurrent();

            $table->index('cartao_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambiguidade_logs');
    }
};
