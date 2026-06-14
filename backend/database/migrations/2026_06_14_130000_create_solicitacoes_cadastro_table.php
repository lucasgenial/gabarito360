<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_cadastro', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome', 180);
            $table->char('documento_hash', 64);
            $table->string('documento_mascarado', 20)->nullable();
            $table->string('email', 254);
            $table->string('perfil_codigo', 60);
            $table->string('status', 30)->default('pendente');
            $table->uuid('consentimento_id')->nullable();
            $table->json('metadados')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();
            $table->foreign('consentimento_id')->references('id')->on('consentimentos')->nullOnDelete();
            $table->index(['status', 'created_at'], 'idx_solicitacoes_cadastro_status');
            $table->index('email', 'idx_solicitacoes_cadastro_email');
        });

        DB::statement("ALTER TABLE solicitacoes_cadastro ADD CONSTRAINT ck_solicitacoes_cadastro_status CHECK (status IN ('pendente', 'aprovada', 'rejeitada'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_cadastro');
    }
};
