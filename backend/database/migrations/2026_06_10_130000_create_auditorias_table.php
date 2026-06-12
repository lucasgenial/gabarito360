<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('request_id')->nullable()->index();
            $table->uuid('usuario_id')->nullable();
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->string('acao', 100);
            $table->string('entidade_tipo', 100);
            $table->uuid('entidade_id')->nullable();
            $table->json('dados_anteriores')->nullable();
            $table->json('dados_novos')->nullable();
            $table->json('metadados')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['entidade_tipo', 'entidade_id', 'created_at'], 'idx_auditorias_entidade');
            $table->index(['usuario_id', 'created_at'], 'idx_auditorias_usuario_data');
            $table->index(['acao', 'created_at'], 'idx_auditorias_acao_data');
            $table->index(['nucleo_id', 'created_at'], 'idx_auditorias_nucleo_data');
            $table->index(['escola_id', 'created_at'], 'idx_auditorias_escola_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
