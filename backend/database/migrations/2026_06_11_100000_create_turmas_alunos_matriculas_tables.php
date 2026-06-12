<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turmas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('escola_id');
            $table->string('codigo', 50);
            $table->string('nome', 120);
            $table->string('serie_ano', 60);
            $table->string('turno', 30)->nullable();
            $table->smallInteger('ano_letivo');
            $table->string('status', 20)->default('ativo');
            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);
            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->unique(['escola_id', 'ano_letivo', 'codigo'], 'uq_turmas_escola_ano_codigo');
            $table->index(['escola_id', 'ano_letivo', 'status'], 'idx_turmas_escola_ano_status');
        });

        Schema::create('alunos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('escola_id');
            $table->string('matricula', 80);
            $table->string('codigo_interno', 80)->nullable();
            $table->string('nome', 180);
            $table->date('data_nascimento')->nullable();
            $table->string('documento', 30)->nullable();
            $table->string('status', 20)->default('ativo');
            $table->text('observacoes')->nullable();
            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);
            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->unique(['escola_id', 'matricula'], 'uq_alunos_escola_matricula');
            $table->index(['escola_id', 'nome'], 'idx_alunos_escola_nome');
            $table->index(['escola_id', 'status'], 'idx_alunos_escola_status');
        });

        Schema::create('matriculas_turmas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('aluno_id');
            $table->uuid('turma_id');
            $table->smallInteger('ano_letivo');
            $table->string('numero_chamada', 20)->nullable();
            $table->string('status', 20)->default('ativa');
            $table->date('inicio_em');
            $table->date('fim_em')->nullable();
            $table->timestamps(6);
            $table->foreign('aluno_id')->references('id')->on('alunos')->restrictOnDelete();
            $table->foreign('turma_id')->references('id')->on('turmas')->restrictOnDelete();
            $table->index(['turma_id', 'status'], 'idx_matriculas_turma_status');
            $table->index(['aluno_id', 'ano_letivo', 'inicio_em'], 'idx_matriculas_aluno_historico');
        });

        DB::statement("ALTER TABLE turmas ADD CONSTRAINT ck_turmas_turno CHECK (turno IS NULL OR turno IN ('matutino', 'vespertino', 'noturno', 'integral'))");
        DB::statement('ALTER TABLE turmas ADD CONSTRAINT ck_turmas_ano_letivo CHECK (ano_letivo BETWEEN 2000 AND 2100)');
        DB::statement("ALTER TABLE turmas ADD CONSTRAINT ck_turmas_status CHECK (status IN ('ativo', 'inativo'))");
        DB::statement("ALTER TABLE alunos ADD CONSTRAINT ck_alunos_status CHECK (status IN ('ativo', 'inativo'))");
        DB::statement("ALTER TABLE matriculas_turmas ADD CONSTRAINT ck_matriculas_turmas_status CHECK (status IN ('ativa', 'transferida', 'encerrada'))");
        DB::statement('ALTER TABLE matriculas_turmas ADD CONSTRAINT ck_matriculas_turmas_periodo CHECK (fim_em IS NULL OR fim_em >= inicio_em)');
        DB::statement("ALTER TABLE matriculas_turmas ADD CONSTRAINT ck_matriculas_turmas_estado_periodo CHECK ((status = 'ativa' AND fim_em IS NULL) OR (status IN ('transferida', 'encerrada') AND fim_em IS NOT NULL))");
        DB::statement("ALTER TABLE matriculas_turmas ADD chave_vigente VARCHAR(255) AS (IF(status = 'ativa', CONCAT(CAST(aluno_id AS CHAR(36)), '|', ano_letivo), NULL)) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_matriculas_aluno_ano_ativa ON matriculas_turmas (chave_vigente)');
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas_turmas');
        Schema::dropIfExists('alunos');
        Schema::dropIfExists('turmas');
    }
};
