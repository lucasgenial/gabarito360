<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelos_cartao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('nucleo_id')->nullable();
            $table->string('nome', 120);
            $table->integer('versao');
            $table->smallInteger('quantidade_questoes');
            $table->smallInteger('quantidade_alternativas');
            $table->json('alternativas');
            $table->string('tipo_codigo', 30);
            $table->string('origem_codigo', 30);
            $table->json('configuracao_omr');
            $table->char('artefato_checksum_sha256', 64)->nullable();
            $table->string('status', 20)->default('rascunho');
            $table->uuid('criado_por')->nullable();
            $table->uuid('homologado_por')->nullable();
            $table->dateTime('homologado_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->restrictOnDelete();
            $table->foreign('criado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('homologado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index(['nucleo_id', 'status'], 'idx_modelos_cartao_nucleo_status');
        });

        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_status CHECK (status IN ('rascunho', 'homologado', 'inativo'))");
        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_tipo_codigo CHECK (tipo_codigo IN ('sem_codigo', 'qr_code', 'codigo_barras', 'ocr_texto'))");
        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_origem_codigo CHECK (origem_codigo IN ('nenhum', 'externo', 'sistema_afixado'))");
        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_codigo_semantica CHECK ((tipo_codigo = 'sem_codigo' AND origem_codigo = 'nenhum') OR (tipo_codigo <> 'sem_codigo' AND origem_codigo <> 'nenhum'))");
        DB::statement('ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_versao CHECK (versao > 0)');
        DB::statement('ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_quantidades CHECK (quantidade_questoes > 0 AND quantidade_alternativas > 1)');
        DB::statement("ALTER TABLE modelos_cartao ADD escopo_unico VARCHAR(36) AS (IFNULL(CAST(nucleo_id AS CHAR(36)), 'global')) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_modelos_cartao_nucleo_nome_versao ON modelos_cartao (escopo_unico, nome, versao)');
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_cartao');
    }
};
