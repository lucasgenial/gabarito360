<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 60)->unique('uq_perfis_codigo');
            $table->string('nome', 120);
            $table->text('descricao')->nullable();
            $table->string('escopo_permitido', 20);
            $table->boolean('sistema')->default(false);
            $table->string('status', 20)->default('ativo')->index('idx_perfis_status');
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();
        });

        Schema::create('permissoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 100)->unique('uq_permissoes_codigo');
            $table->text('descricao')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();
        });

        Schema::create('perfil_permissoes', function (Blueprint $table) {
            $table->uuid('perfil_id');
            $table->uuid('permissao_id');
            $table->dateTime('created_at', 6)->useCurrent();
            $table->primary(['perfil_id', 'permissao_id']);
            $table->foreign('perfil_id')->references('id')->on('perfis')->cascadeOnDelete();
            $table->foreign('permissao_id')->references('id')->on('permissoes')->cascadeOnDelete();
        });

        Schema::create('usuario_perfis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id');
            $table->uuid('perfil_id');
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->uuid('concedido_por')->nullable();
            $table->dateTime('inicio_at', 6)->useCurrent();
            $table->dateTime('fim_at', 6)->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->foreign('usuario_id')->references('id')->on('usuarios');
            $table->foreign('perfil_id')->references('id')->on('perfis');
            $table->foreign('concedido_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['usuario_id', 'fim_at'], 'idx_usuario_perfis_usuario_ativo');
            $table->index(['nucleo_id', 'perfil_id', 'fim_at'], 'idx_usuario_perfis_nucleo');
            $table->index(['escola_id', 'perfil_id', 'fim_at'], 'idx_usuario_perfis_escola');
        });

        DB::statement("ALTER TABLE perfis ADD CONSTRAINT ck_perfis_escopo_permitido CHECK (escopo_permitido IN ('global', 'nucleo', 'escola', 'operacional'))");
        DB::statement("ALTER TABLE perfis ADD CONSTRAINT ck_perfis_status CHECK (status IN ('ativo', 'inativo'))");
        DB::statement('ALTER TABLE usuario_perfis ADD CONSTRAINT ck_usuario_perfis_escopo CHECK ((nucleo_id IS NULL) OR (escola_id IS NULL))');
        DB::statement('ALTER TABLE usuario_perfis ADD CONSTRAINT ck_usuario_perfis_periodo CHECK (fim_at IS NULL OR fim_at >= inicio_at)');
        DB::statement("ALTER TABLE usuario_perfis ADD chave_vigente VARCHAR(255) AS (IF(fim_at IS NULL, CONCAT(CAST(usuario_id AS CHAR(36)), '|', CAST(perfil_id AS CHAR(36)), '|', IFNULL(CAST(nucleo_id AS CHAR(36)), ''), '|', IFNULL(CAST(escola_id AS CHAR(36)), '')), NULL)) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_usuario_perfis_ativo ON usuario_perfis (chave_vigente)');
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_perfis');
        Schema::dropIfExists('perfil_permissoes');
        Schema::dropIfExists('permissoes');
        Schema::dropIfExists('perfis');
    }
};
