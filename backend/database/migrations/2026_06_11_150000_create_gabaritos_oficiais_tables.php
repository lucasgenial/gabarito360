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
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('prova_id');
            $table->integer('versao');
            $table->string('status', 20)->default('rascunho');
            $table->text('justificativa')->nullable();
            $table->uuid('criado_por')->nullable();
            $table->uuid('publicado_por')->nullable();
            $table->timestampTz('publicado_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('prova_id')->references('id')->on('provas')->restrictOnDelete();
            $table->foreign('criado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('publicado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE gabaritos_oficiais ADD CONSTRAINT ck_gabaritos_oficiais_status CHECK (status IN ('rascunho', 'vigente', 'substituido'))");
        DB::statement('ALTER TABLE gabaritos_oficiais ADD CONSTRAINT ck_gabaritos_oficiais_versao CHECK (versao > 0)');
        DB::statement("ALTER TABLE gabaritos_oficiais ADD CONSTRAINT ck_gabaritos_oficiais_publicacao CHECK ((status = 'rascunho' AND publicado_por IS NULL AND publicado_at IS NULL) OR (status = 'vigente' AND publicado_por IS NOT NULL AND publicado_at IS NOT NULL) OR (status = 'substituido' AND publicado_por IS NOT NULL AND publicado_at IS NOT NULL AND justificativa IS NOT NULL AND length(trim(justificativa)) > 0))");
        DB::statement('CREATE UNIQUE INDEX uq_gabaritos_oficiais_prova_versao ON gabaritos_oficiais (prova_id, versao)');
        DB::statement("CREATE UNIQUE INDEX uq_gabaritos_oficiais_vigente ON gabaritos_oficiais (prova_id) WHERE status = 'vigente'");
        DB::statement('CREATE UNIQUE INDEX uq_gabaritos_oficiais_id_prova ON gabaritos_oficiais (id, prova_id)');
        DB::statement('CREATE INDEX idx_gabaritos_oficiais_prova_status ON gabaritos_oficiais (prova_id, status)');

        Schema::create('gabarito_respostas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('prova_id');
            $table->uuid('gabarito_oficial_id');
            $table->uuid('questao_id');
            $table->string('alternativa_correta', 10)->nullable();
            $table->boolean('anulada')->default(false);
            $table->decimal('peso', 10, 4)->default(1);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        DB::statement('ALTER TABLE gabarito_respostas ADD CONSTRAINT ck_gabarito_respostas_alternativa CHECK ((anulada AND alternativa_correta IS NULL) OR (NOT anulada AND alternativa_correta IS NOT NULL))');
        DB::statement('ALTER TABLE gabarito_respostas ADD CONSTRAINT ck_gabarito_respostas_peso CHECK (peso >= 0)');
        DB::statement('ALTER TABLE gabarito_respostas ADD CONSTRAINT fk_gabarito_respostas_gabarito_prova FOREIGN KEY (gabarito_oficial_id, prova_id) REFERENCES gabaritos_oficiais (id, prova_id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE gabarito_respostas ADD CONSTRAINT fk_gabarito_respostas_questao_prova FOREIGN KEY (questao_id, prova_id) REFERENCES questoes (id, prova_id) ON DELETE RESTRICT');
        DB::statement('CREATE UNIQUE INDEX uq_gabarito_respostas_gabarito_questao ON gabarito_respostas (gabarito_oficial_id, questao_id)');
        DB::statement('CREATE INDEX idx_gabarito_respostas_questao ON gabarito_respostas (questao_id)');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION proteger_gabarito_oficial() RETURNS trigger AS $$
            DECLARE
                prova_status varchar;
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'Gabaritos oficiais nao podem ser excluidos.';
                END IF;

                SELECT status INTO prova_status FROM provas WHERE id = NEW.prova_id AND deleted_at IS NULL;

                IF TG_OP = 'INSERT' THEN
                    IF NEW.status <> 'rascunho' OR prova_status <> 'rascunho' THEN
                        RAISE EXCEPTION 'Gabarito somente pode ser criado como rascunho de prova rascunho.';
                    END IF;

                    RETURN NEW;
                END IF;

                IF OLD.status <> 'rascunho' THEN
                    RAISE EXCEPTION 'Gabarito vigente ou substituido e imutavel.';
                END IF;

                IF NEW.prova_id IS DISTINCT FROM OLD.prova_id
                    OR NEW.versao IS DISTINCT FROM OLD.versao
                    OR NEW.criado_por IS DISTINCT FROM OLD.criado_por THEN
                    RAISE EXCEPTION 'Prova, versao e criador do gabarito sao imutaveis.';
                END IF;

                IF NEW.status = 'substituido' THEN
                    RAISE EXCEPTION 'Gabarito rascunho nao pode ser substituido diretamente.';
                END IF;

                IF prova_status <> 'rascunho' THEN
                    RAISE EXCEPTION 'Gabarito somente pode mudar enquanto a prova estiver em rascunho.';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_proteger_gabarito_oficial
            BEFORE INSERT OR UPDATE OR DELETE ON gabaritos_oficiais
            FOR EACH ROW EXECUTE FUNCTION proteger_gabarito_oficial();

            CREATE OR REPLACE FUNCTION validar_prova_respostas_oficiais() RETURNS trigger AS $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM gabarito_respostas
                    WHERE prova_id = NEW.id
                        AND NOT anulada
                        AND NOT (NEW.alternativas ? alternativa_correta)
                ) THEN
                    RAISE EXCEPTION 'Alternativas da prova invalidariam respostas oficiais existentes.';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_validar_prova_respostas_oficiais
            BEFORE UPDATE OF alternativas ON provas
            FOR EACH ROW EXECUTE FUNCTION validar_prova_respostas_oficiais();

            CREATE OR REPLACE FUNCTION validar_gabarito_resposta_rascunho() RETURNS trigger AS $$
            DECLARE
                gabarito_status varchar;
                gabarito_prova uuid;
                prova_status varchar;
                prova_alternativas jsonb;
                questao_status varchar;
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'Respostas oficiais nao podem ser excluidas.';
                END IF;

                IF TG_OP = 'UPDATE' AND (
                    NEW.prova_id IS DISTINCT FROM OLD.prova_id
                    OR NEW.gabarito_oficial_id IS DISTINCT FROM OLD.gabarito_oficial_id
                    OR NEW.questao_id IS DISTINCT FROM OLD.questao_id
                ) THEN
                    RAISE EXCEPTION 'Referencias da resposta oficial sao imutaveis.';
                END IF;

                SELECT g.status, g.prova_id, p.status, p.alternativas
                INTO gabarito_status, gabarito_prova, prova_status, prova_alternativas
                FROM gabaritos_oficiais g
                JOIN provas p ON p.id = g.prova_id AND p.deleted_at IS NULL
                WHERE g.id = NEW.gabarito_oficial_id;

                IF gabarito_status IS NULL OR gabarito_prova <> NEW.prova_id THEN
                    RAISE EXCEPTION 'Gabarito nao pertence a prova informada.';
                END IF;

                IF gabarito_status <> 'rascunho' OR prova_status <> 'rascunho' THEN
                    RAISE EXCEPTION 'Respostas oficiais somente podem mudar em gabarito e prova rascunho.';
                END IF;

                SELECT status INTO questao_status
                FROM questoes
                WHERE id = NEW.questao_id AND prova_id = NEW.prova_id;

                IF questao_status IS NULL OR questao_status <> 'ativa' THEN
                    RAISE EXCEPTION 'Resposta oficial exige questao ativa da mesma prova.';
                END IF;

                IF NOT NEW.anulada
                    AND (NEW.alternativa_correta IS NULL OR NOT (prova_alternativas ? NEW.alternativa_correta)) THEN
                    RAISE EXCEPTION 'Alternativa correta nao pertence a prova.';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_validar_gabarito_resposta_rascunho
            BEFORE INSERT OR UPDATE OR DELETE ON gabarito_respostas
            FOR EACH ROW EXECUTE FUNCTION validar_gabarito_resposta_rascunho();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_validar_gabarito_resposta_rascunho ON gabarito_respostas');
        DB::unprepared('DROP FUNCTION IF EXISTS validar_gabarito_resposta_rascunho()');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_validar_prova_respostas_oficiais ON provas');
        DB::unprepared('DROP FUNCTION IF EXISTS validar_prova_respostas_oficiais()');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_proteger_gabarito_oficial ON gabaritos_oficiais');
        DB::unprepared('DROP FUNCTION IF EXISTS proteger_gabarito_oficial()');
        Schema::dropIfExists('gabarito_respostas');
        Schema::dropIfExists('gabaritos_oficiais');
    }
};
