<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessoes_usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id');
            $table->unsignedBigInteger('personal_access_token_id')->nullable();
            $table->string('dispositivo', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->boolean('manter_conectado')->default(false);
            $table->dateTime('criado_em', 6)->useCurrent();
            $table->dateTime('ultimo_acesso_at', 6)->nullable();
            $table->dateTime('encerrado_at', 6)->nullable();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->foreign('personal_access_token_id')->references('id')->on('personal_access_tokens')->nullOnDelete();
            $table->index(['usuario_id', 'encerrado_at'], 'idx_sessoes_usuarios_usuario_ativas');
            $table->index('personal_access_token_id', 'idx_sessoes_usuarios_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessoes_usuarios');
    }
};
