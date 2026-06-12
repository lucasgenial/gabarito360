<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->uuid('modelo_cartao_id');
            $table->string('codigo', 60);
            $table->string('titulo', 180);
            $table->text('descricao')->nullable();
            $table->string('tipo', 50);
            $table->string('nivel', 80)->nullable();
            $table->smallInteger('ano_referencia')->nullable();
            $table->smallInteger('quantidade_questoes');
            $table->smallInteger('quantidade_alternativas');
            $table->json('alternativas');
            $table->string('status', 30)->default('rascunho');
            $table->uuid('criado_por')->nullable();
            $table->dateTime('publicada_at', 6)->nullable();
            $table->dateTime('finalizada_at', 6)->nullable();
            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->restrictOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->foreign('modelo_cartao_id')->references('id')->on('modelos_cartao')->restrictOnDelete();
            $table->foreign('criado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index(['nucleo_id', 'status'], 'idx_provas_nucleo_status');
            $table->index(['escola_id', 'status'], 'idx_provas_escola_status');
            $table->index('modelo_cartao_id', 'idx_provas_modelo_cartao');
            $table->index('criado_por', 'idx_provas_criado_por');
        });

        DB::statement('ALTER TABLE provas ADD CONSTRAINT ck_provas_proprietario CHECK ((nucleo_id IS NULL) <> (escola_id IS NULL))');
        DB::statement("ALTER TABLE provas ADD CONSTRAINT ck_provas_status CHECK (status IN ('rascunho', 'publicada', 'finalizada', 'arquivada'))");
        DB::statement('ALTER TABLE provas ADD CONSTRAINT ck_provas_quantidades CHECK (quantidade_questoes > 0 AND quantidade_alternativas > 1)');
        DB::statement('ALTER TABLE provas ADD CONSTRAINT ck_provas_ano_referencia CHECK (ano_referencia IS NULL OR ano_referencia BETWEEN 2000 AND 2100)');
        DB::statement('ALTER TABLE provas ADD proprietario_unico VARCHAR(36) AS (COALESCE(CAST(nucleo_id AS CHAR(36)), CAST(escola_id AS CHAR(36)))) PERSISTENT');
        DB::statement('CREATE UNIQUE INDEX uq_provas_proprietario_codigo ON provas (proprietario_unico, codigo)');

        Schema::create('questoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prova_id');
            $table->smallInteger('numero');
            $table->string('codigo', 50)->nullable();
            $table->decimal('peso_padrao', 10, 4)->default(1);
            $table->string('status', 20)->default('ativa');
            $table->timestamps(6);
            $table->foreign('prova_id')->references('id')->on('provas')->restrictOnDelete();
            $table->unique(['prova_id', 'numero'], 'uq_questoes_prova_numero');
            $table->unique(['id', 'prova_id'], 'uq_questoes_id_prova');
            $table->index(['prova_id', 'status'], 'idx_questoes_prova_status');
        });

        DB::statement('ALTER TABLE questoes ADD CONSTRAINT ck_questoes_numero CHECK (numero > 0)');
        DB::statement('ALTER TABLE questoes ADD CONSTRAINT ck_questoes_peso_padrao CHECK (peso_padrao >= 0)');
        DB::statement("ALTER TABLE questoes ADD CONSTRAINT ck_questoes_status CHECK (status IN ('ativa', 'inativa'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('questoes');
        Schema::dropIfExists('provas');
    }
};
