<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prova_turmas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('prova_id');
            $table->uuid('turma_id');
            $table->date('data_prevista')->nullable();
            $table->uuid('vinculado_por')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('prova_id')->references('id')->on('provas')->restrictOnDelete();
            $table->foreign('turma_id')->references('id')->on('turmas')->restrictOnDelete();
            $table->foreign('vinculado_por')->references('id')->on('usuarios')->nullOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX uq_prova_turmas_prova_turma ON prova_turmas (prova_id, turma_id)');
        DB::statement('CREATE INDEX idx_prova_turmas_turma_data ON prova_turmas (turma_id, data_prevista)');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION validar_prova_turma() RETURNS trigger AS $$
            DECLARE
                prova_status varchar;
                prova_nucleo uuid;
                prova_escola uuid;
                turma_status varchar;
                turma_escola uuid;
                escola_status varchar;
                escola_nucleo uuid;
                nucleo_status varchar;
            BEGIN
                SELECT status, nucleo_id, escola_id
                INTO prova_status, prova_nucleo, prova_escola
                FROM provas
                WHERE id = NEW.prova_id AND deleted_at IS NULL;

                SELECT t.status, t.escola_id, e.status, e.nucleo_id, n.status
                INTO turma_status, turma_escola, escola_status, escola_nucleo, nucleo_status
                FROM turmas t
                JOIN escolas e ON e.id = t.escola_id AND e.deleted_at IS NULL
                JOIN nucleos n ON n.id = e.nucleo_id AND n.deleted_at IS NULL
                WHERE t.id = NEW.turma_id AND t.deleted_at IS NULL;

                IF prova_status <> 'publicada' THEN
                    RAISE EXCEPTION 'Somente prova publicada pode ser vinculada a turma.';
                END IF;

                IF turma_status <> 'ativo' OR escola_status <> 'ativo' OR nucleo_status <> 'ativo' THEN
                    RAISE EXCEPTION 'Turma, escola e nucleo devem estar ativos.';
                END IF;

                IF (prova_nucleo IS NOT NULL AND prova_nucleo <> escola_nucleo)
                    OR (prova_escola IS NOT NULL AND prova_escola <> turma_escola) THEN
                    RAISE EXCEPTION 'A turma nao pertence ao escopo proprietario da prova.';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_validar_prova_turma
            BEFORE INSERT OR UPDATE ON prova_turmas
            FOR EACH ROW EXECUTE FUNCTION validar_prova_turma();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_validar_prova_turma ON prova_turmas');
        DB::unprepared('DROP FUNCTION IF EXISTS validar_prova_turma()');
        Schema::dropIfExists('prova_turmas');
    }
};
