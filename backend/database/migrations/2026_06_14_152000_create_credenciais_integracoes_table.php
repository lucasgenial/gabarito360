<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credenciais_integracoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('integracao_id');
            $table->string('chave', 120);
            $table->text('valor_criptografado');
            $table->timestamps(6);
            $table->foreign('integracao_id')->references('id')->on('integracoes')->cascadeOnDelete();
            $table->unique(['integracao_id', 'chave'], 'uq_cred_integracao_chave');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credenciais_integracoes');
    }
};
