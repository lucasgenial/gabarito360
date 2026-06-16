<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sincronizacoes_seges', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['sucesso', 'erro', 'parcial']);
            $table->timestamp('iniciado_em')->useCurrent();
            $table->timestamp('concluido_em')->nullable();
            $table->integer('duracao_minutos')->nullable();
            $table->json('detalhes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizacoes_seges');
    }
};
