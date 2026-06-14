<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historicos_acesso', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id')->nullable();
            $table->string('evento', 40);
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->uuid('sessao_id')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('sessao_id')->references('id')->on('sessoes_usuarios')->nullOnDelete();
            $table->index(['usuario_id', 'created_at'], 'idx_historicos_acesso_usuario_data');
            $table->index(['evento', 'created_at'], 'idx_historicos_acesso_evento_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historicos_acesso');
    }
};
