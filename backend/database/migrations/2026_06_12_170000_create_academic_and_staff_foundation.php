<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escolas', function (Blueprint $table) {
            $table->string('inep', 20)->nullable()->after('nome')->unique('uq_escolas_inep');
        });

        Schema::create('cargos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 80)->unique('uq_cargos_codigo');
            $table->string('nome', 120);
            $table->string('descricao', 500)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps(6);
            $table->index(['ativo', 'nome'], 'idx_cargos_ativo_nome');
        });

        Schema::create('periodos_letivos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('escola_id');
            $table->string('nome', 100);
            $table->smallInteger('ano');
            $table->string('tipo', 30);
            $table->date('inicio_em');
            $table->date('fim_em');
            $table->string('status', 20)->default('ativo');
            $table->timestamps(6);
            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->unique(['escola_id', 'ano', 'nome'], 'uq_periodos_escola_ano_nome');
            $table->index(['escola_id', 'status', 'inicio_em'], 'idx_periodos_escola_status_inicio');
        });

        Schema::create('series_anos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 40)->unique('uq_series_anos_codigo');
            $table->string('nome', 100);
            $table->unsignedSmallInteger('ordem');
            $table->string('nivel', 40);
            $table->boolean('ativo')->default(true);
            $table->timestamps(6);
            $table->index(['ativo', 'ordem'], 'idx_series_anos_ativo_ordem');
        });

        Schema::create('disciplinas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 40)->unique('uq_disciplinas_codigo');
            $table->string('nome', 120);
            $table->string('cor_token', 80)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps(6);
            $table->index(['ativo', 'nome'], 'idx_disciplinas_ativo_nome');
        });

        Schema::create('usuario_lotacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id');
            $table->uuid('cargo_id');
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->string('matricula_funcional', 60)->nullable();
            $table->date('inicio_em');
            $table->date('fim_em')->nullable();
            $table->boolean('principal')->default(false);
            $table->timestamps(6);
            $table->foreign('usuario_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('cargo_id')->references('id')->on('cargos')->restrictOnDelete();
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->restrictOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->index(['escola_id', 'fim_em', 'cargo_id'], 'idx_lotacoes_escola_vigencia_cargo');
            $table->index(['usuario_id', 'fim_em'], 'idx_lotacoes_usuario_vigencia');
        });

        Schema::create('usuario_disciplinas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id');
            $table->uuid('disciplina_id');
            $table->uuid('escola_id')->nullable();
            $table->date('inicio_em');
            $table->date('fim_em')->nullable();
            $table->timestamps(6);
            $table->foreign('usuario_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('disciplina_id')->references('id')->on('disciplinas')->restrictOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->index(['usuario_id', 'fim_em'], 'idx_usuario_disciplinas_usuario_vigencia');
            $table->index(['disciplina_id', 'escola_id', 'fim_em'], 'idx_usuario_disciplinas_escopo');
        });

        Schema::table('turmas', function (Blueprint $table) {
            $table->uuid('periodo_letivo_id')->nullable()->after('escola_id');
            $table->uuid('serie_ano_id')->nullable()->after('periodo_letivo_id');
            $table->unsignedSmallInteger('capacidade')->nullable()->after('turno');
            $table->foreign('periodo_letivo_id')->references('id')->on('periodos_letivos')->restrictOnDelete();
            $table->foreign('serie_ano_id')->references('id')->on('series_anos')->restrictOnDelete();
            $table->index(['escola_id', 'status', 'serie_ano_id'], 'idx_turmas_escola_status_serie');
        });

        DB::statement("ALTER TABLE periodos_letivos ADD CONSTRAINT ck_periodos_tipo CHECK (tipo IN ('anual', 'semestre', 'trimestre', 'bimestre'))");
        DB::statement("ALTER TABLE periodos_letivos ADD CONSTRAINT ck_periodos_status CHECK (status IN ('ativo', 'encerrado', 'inativo'))");
        DB::statement('ALTER TABLE periodos_letivos ADD CONSTRAINT ck_periodos_datas CHECK (fim_em >= inicio_em)');
        DB::statement('ALTER TABLE usuario_lotacoes ADD CONSTRAINT ck_lotacoes_escopo CHECK ((nucleo_id IS NULL) <> (escola_id IS NULL))');
        DB::statement('ALTER TABLE usuario_lotacoes ADD CONSTRAINT ck_lotacoes_periodo CHECK (fim_em IS NULL OR fim_em >= inicio_em)');
        DB::statement("ALTER TABLE usuario_lotacoes ADD chave_vigente VARCHAR(255) AS (IF(fim_em IS NULL, CONCAT(CAST(usuario_id AS CHAR(36)), '|', CAST(cargo_id AS CHAR(36)), '|', IFNULL(CAST(nucleo_id AS CHAR(36)), ''), '|', IFNULL(CAST(escola_id AS CHAR(36)), '')), NULL)) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_usuario_lotacoes_vigente ON usuario_lotacoes (chave_vigente)');
        DB::statement('ALTER TABLE usuario_disciplinas ADD CONSTRAINT ck_usuario_disciplinas_periodo CHECK (fim_em IS NULL OR fim_em >= inicio_em)');
        DB::statement("ALTER TABLE usuario_disciplinas ADD chave_vigente VARCHAR(255) AS (IF(fim_em IS NULL, CONCAT(CAST(usuario_id AS CHAR(36)), '|', CAST(disciplina_id AS CHAR(36)), '|', IFNULL(CAST(escola_id AS CHAR(36)), '')), NULL)) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_usuario_disciplinas_vigente ON usuario_disciplinas (chave_vigente)');
    }

    public function down(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->dropForeign(['periodo_letivo_id']);
            $table->dropForeign(['serie_ano_id']);
            $table->dropIndex('idx_turmas_escola_status_serie');
            $table->dropColumn(['periodo_letivo_id', 'serie_ano_id', 'capacidade']);
        });
        Schema::dropIfExists('usuario_disciplinas');
        Schema::dropIfExists('usuario_lotacoes');
        Schema::dropIfExists('disciplinas');
        Schema::dropIfExists('series_anos');
        Schema::dropIfExists('periodos_letivos');
        Schema::dropIfExists('cargos');
        Schema::table('escolas', fn (Blueprint $table) => $table->dropColumn('inep'));
    }
};
