<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integracoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('escopo', 20)->default('global');
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->string('chave', 80);
            $table->string('nome', 180)->nullable();
            $table->string('descricao', 500)->nullable();
            $table->string('status', 20)->default('desconectada');
            $table->dateTime('ultima_execucao', 6)->nullable();
            $table->dateTime('ultima_sincronizacao', 6)->nullable();
            $table->json('erros')->nullable();
            $table->boolean('ativa')->default(true);
            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->restrictOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->unique(['escopo', 'nucleo_id', 'escola_id', 'chave'], 'uq_integracoes_escopo_chave');
            $table->index('status', 'idx_integracoes_status');
        });

        DB::statement("ALTER TABLE integracoes ADD CONSTRAINT ck_integracoes_status CHECK (status IN ('conectada', 'pendente', 'erro', 'desconectada'))");
        DB::statement("ALTER TABLE integracoes ADD CONSTRAINT ck_integracoes_escopo CHECK (escopo IN ('global', 'nucleo', 'escola'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('integracoes');
    }
};
