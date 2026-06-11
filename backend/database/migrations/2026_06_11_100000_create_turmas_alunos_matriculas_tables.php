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
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('escola_id');
            $table->string('codigo', 50);
            $table->string('nome', 120);
            $table->string('serie_ano', 60);
            $table->string('turno', 30)->nullable();
            $table->smallInteger('ano_letivo');
            $table->string('status', 20)->default('ativo');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE turmas ADD CONSTRAINT ck_turmas_turno CHECK (turno IS NULL OR turno IN ('matutino', 'vespertino', 'noturno', 'integral'))");
        DB::statement('ALTER TABLE turmas ADD CONSTRAINT ck_turmas_ano_letivo CHECK (ano_letivo BETWEEN 2000 AND 2100)');
        DB::statement("ALTER TABLE turmas ADD CONSTRAINT ck_turmas_status CHECK (status IN ('ativo', 'inativo'))");
        DB::statement('CREATE UNIQUE INDEX uq_turmas_escola_ano_codigo ON turmas (escola_id, ano_letivo, lower(codigo)) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_turmas_escola_ano_status ON turmas (escola_id, ano_letivo, status) WHERE deleted_at IS NULL');

        // Prerequisito referencial do MP-020; o CRUD de alunos pertence ao MP-021.
        Schema::create('alunos', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('escola_id');
            $table->string('matricula', 80);
            $table->string('codigo_interno', 80)->nullable();
            $table->string('nome', 180);
            $table->date('data_nascimento')->nullable();
            $table->string('documento', 30)->nullable();
            $table->string('status', 20)->default('ativo');
            $table->text('observacoes')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE alunos ADD CONSTRAINT ck_alunos_status CHECK (status IN ('ativo', 'inativo'))");
        DB::statement('CREATE UNIQUE INDEX uq_alunos_escola_matricula ON alunos (escola_id, lower(matricula)) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_alunos_escola_nome ON alunos (escola_id, nome) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_alunos_escola_status ON alunos (escola_id, status) WHERE deleted_at IS NULL');

        Schema::create('matriculas_turmas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('aluno_id');
            $table->uuid('turma_id');
            $table->smallInteger('ano_letivo');
            $table->string('numero_chamada', 20)->nullable();
            $table->string('status', 20)->default('ativa');
            $table->date('inicio_em');
            $table->date('fim_em')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('aluno_id')->references('id')->on('alunos')->restrictOnDelete();
            $table->foreign('turma_id')->references('id')->on('turmas')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE matriculas_turmas ADD CONSTRAINT ck_matriculas_turmas_status CHECK (status IN ('ativa', 'transferida', 'encerrada'))");
        DB::statement('ALTER TABLE matriculas_turmas ADD CONSTRAINT ck_matriculas_turmas_periodo CHECK (fim_em IS NULL OR fim_em >= inicio_em)');
        DB::statement("ALTER TABLE matriculas_turmas ADD CONSTRAINT ck_matriculas_turmas_estado_periodo CHECK ((status = 'ativa' AND fim_em IS NULL) OR (status IN ('transferida', 'encerrada') AND fim_em IS NOT NULL))");
        DB::statement('CREATE UNIQUE INDEX uq_matriculas_aluno_ano_ativa ON matriculas_turmas (aluno_id, ano_letivo) WHERE status = \'ativa\'');
        DB::statement('CREATE INDEX idx_matriculas_turma_status ON matriculas_turmas (turma_id, status)');
        DB::statement('CREATE INDEX idx_matriculas_aluno_historico ON matriculas_turmas (aluno_id, ano_letivo, inicio_em)');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION validar_matricula_turma() RETURNS trigger AS $$
            DECLARE
                aluno_escola uuid;
                turma_escola uuid;
                turma_ano smallint;
            BEGIN
                SELECT escola_id INTO aluno_escola FROM alunos WHERE id = NEW.aluno_id;
                SELECT escola_id, ano_letivo INTO turma_escola, turma_ano FROM turmas WHERE id = NEW.turma_id;

                IF aluno_escola IS NULL OR turma_escola IS NULL THEN
                    RAISE EXCEPTION 'Aluno ou turma inexistente para matricula.';
                END IF;

                IF aluno_escola <> turma_escola THEN
                    RAISE EXCEPTION 'Aluno e turma devem pertencer a mesma escola.';
                END IF;

                IF NEW.ano_letivo <> turma_ano THEN
                    RAISE EXCEPTION 'Ano letivo da matricula deve corresponder ao da turma.';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_validar_matricula_turma
            BEFORE INSERT OR UPDATE OF aluno_id, turma_id, ano_letivo
            ON matriculas_turmas
            FOR EACH ROW EXECUTE FUNCTION validar_matricula_turma();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_validar_matricula_turma ON matriculas_turmas');
        DB::unprepared('DROP FUNCTION IF EXISTS validar_matricula_turma()');
        Schema::dropIfExists('matriculas_turmas');
        Schema::dropIfExists('alunos');
        Schema::dropIfExists('turmas');
    }
};
