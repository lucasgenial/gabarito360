<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id')->nullable();
            $table->string('chave', 255);
            $table->string('metodo', 10);
            $table->string('rota', 255);
            $table->string('request_hash', 64);
            $table->string('status', 20)->default('processando');
            $table->unsignedSmallInteger('resposta_status')->nullable();
            $table->json('resposta_corpo')->nullable();
            $table->json('resposta_headers')->nullable();
            $table->dateTime('expira_em', 6);
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->unique(['usuario_id', 'chave', 'metodo', 'rota'], 'uq_idempotency_keys_usuario_chave_rota');
            $table->index('expira_em', 'idx_idempotency_keys_expira_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
