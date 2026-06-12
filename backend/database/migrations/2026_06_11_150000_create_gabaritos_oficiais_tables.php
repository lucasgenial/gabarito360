<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gabaritos_oficiais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prova_id');
            $table->integer('versao');
            $table->string('status', 20)->default('rascunho');
            $table->text('justificativa')->nullable();
            $table->uuid('criado_por')->nullable();
            $table->uuid('publicado_por')->nullable();
            $table->dateTime('publicado_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('prova_id')->references('id')->on('provas')->restrictOnDelete();
            $table->foreign('criado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('publicado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->unique(['prova_id', 'versao'], 'uq_gabaritos_oficiais_prova_versao');
            $table->unique(['id', 'prova_id'], 'uq_gabaritos_oficiais_id_prova');
            $table->index(['prova_id', 'status'], 'idx_gabaritos_oficiais_prova_status');
        });

        DB::statement("ALTER TABLE gabaritos_oficiais ADD CONSTRAINT ck_gabaritos_oficiais_status CHECK (status IN ('rascunho', 'vigente', 'substituido'))");
        DB::statement('ALTER TABLE gabaritos_oficiais ADD CONSTRAINT ck_gabaritos_oficiais_versao CHECK (versao > 0)');
        DB::statement("ALTER TABLE gabaritos_oficiais ADD chave_vigente VARCHAR(36) AS (IF(status = 'vigente', CAST(prova_id AS CHAR(36)), NULL)) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_gabaritos_oficiais_vigente ON gabaritos_oficiais (chave_vigente)');

        Schema::create('gabarito_respostas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prova_id');
            $table->uuid('gabarito_oficial_id');
            $table->uuid('questao_id');
            $table->string('alternativa_correta', 10)->nullable();
            $table->boolean('anulada')->default(false);
            $table->decimal('peso', 10, 4)->default(1);
            $table->timestamps(6);
            $table->foreign(['gabarito_oficial_id', 'prova_id'], 'fk_gabarito_respostas_gabarito_prova')->references(['id', 'prova_id'])->on('gabaritos_oficiais')->restrictOnDelete();
            $table->foreign(['questao_id', 'prova_id'], 'fk_gabarito_respostas_questao_prova')->references(['id', 'prova_id'])->on('questoes')->restrictOnDelete();
            $table->unique(['gabarito_oficial_id', 'questao_id'], 'uq_gabarito_respostas_gabarito_questao');
            $table->index('questao_id', 'idx_gabarito_respostas_questao');
        });

        DB::statement('ALTER TABLE gabarito_respostas ADD CONSTRAINT ck_gabarito_respostas_alternativa CHECK ((anulada = 1 AND alternativa_correta IS NULL) OR (anulada = 0 AND alternativa_correta IS NOT NULL))');
        DB::statement('ALTER TABLE gabarito_respostas ADD CONSTRAINT ck_gabarito_respostas_peso CHECK (peso >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('gabarito_respostas');
        Schema::dropIfExists('gabaritos_oficiais');
    }
};
