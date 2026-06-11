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
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
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
            $table->jsonb('alternativas');
            $table->string('status', 30)->default('rascunho');
            $table->uuid('criado_por')->nullable();
            $table->timestampTz('publicada_at')->nullable();
            $table->timestampTz('finalizada_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->foreign('nucleo_id')->references('id')->on('nucleos')->restrictOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->foreign('modelo_cartao_id')->references('id')->on('modelos_cartao')->restrictOnDelete();
            $table->foreign('criado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE provas ADD CONSTRAINT ck_provas_proprietario CHECK (num_nonnulls(nucleo_id, escola_id) = 1)');
        DB::statement("ALTER TABLE provas ADD CONSTRAINT ck_provas_status CHECK (status IN ('rascunho', 'publicada', 'finalizada', 'arquivada'))");
        DB::statement('ALTER TABLE provas ADD CONSTRAINT ck_provas_quantidades CHECK (quantidade_questoes > 0 AND quantidade_alternativas > 1)');
        DB::statement('ALTER TABLE provas ADD CONSTRAINT ck_provas_ano_referencia CHECK (ano_referencia IS NULL OR ano_referencia BETWEEN 2000 AND 2100)');
        DB::statement("ALTER TABLE provas ADD CONSTRAINT ck_provas_alternativas_json CHECK (jsonb_typeof(alternativas) = 'array')");
        DB::statement("ALTER TABLE provas ADD CONSTRAINT ck_provas_datas_status CHECK ((status = 'rascunho' AND publicada_at IS NULL AND finalizada_at IS NULL) OR (status = 'publicada' AND publicada_at IS NOT NULL AND finalizada_at IS NULL) OR (status = 'finalizada' AND publicada_at IS NOT NULL AND finalizada_at IS NOT NULL) OR status = 'arquivada')");
        DB::statement('CREATE UNIQUE INDEX uq_provas_nucleo_codigo ON provas (nucleo_id, lower(codigo)) WHERE nucleo_id IS NOT NULL AND deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX uq_provas_escola_codigo ON provas (escola_id, lower(codigo)) WHERE escola_id IS NOT NULL AND deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_provas_nucleo_status ON provas (nucleo_id, status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_provas_escola_status ON provas (escola_id, status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_provas_modelo_cartao ON provas (modelo_cartao_id)');

        Schema::create('questoes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('prova_id');
            $table->smallInteger('numero');
            $table->string('codigo', 50)->nullable();
            $table->decimal('peso_padrao', 10, 4)->default(1);
            $table->string('status', 20)->default('ativa');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('prova_id')->references('id')->on('provas')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE questoes ADD CONSTRAINT ck_questoes_numero CHECK (numero > 0)');
        DB::statement('ALTER TABLE questoes ADD CONSTRAINT ck_questoes_peso_padrao CHECK (peso_padrao >= 0)');
        DB::statement("ALTER TABLE questoes ADD CONSTRAINT ck_questoes_status CHECK (status IN ('ativa', 'inativa'))");
        DB::statement('CREATE UNIQUE INDEX uq_questoes_prova_numero ON questoes (prova_id, numero)');
        DB::statement('CREATE UNIQUE INDEX uq_questoes_id_prova ON questoes (id, prova_id)');
        DB::statement('CREATE INDEX idx_questoes_prova_status ON questoes (prova_id, status)');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION validar_prova_modelo_cartao() RETURNS trigger AS $$
            DECLARE
                proprietario_nucleo uuid;
                modelo_nucleo uuid;
                modelo_status varchar;
                modelo_questoes smallint;
                modelo_alternativas_quantidade smallint;
                modelo_alternativas jsonb;
            BEGIN
                IF NEW.nucleo_id IS NOT NULL THEN
                    proprietario_nucleo := NEW.nucleo_id;
                ELSE
                    SELECT nucleo_id INTO proprietario_nucleo FROM escolas WHERE id = NEW.escola_id AND deleted_at IS NULL;
                END IF;

                SELECT nucleo_id, status, quantidade_questoes, quantidade_alternativas, alternativas
                INTO modelo_nucleo, modelo_status, modelo_questoes, modelo_alternativas_quantidade, modelo_alternativas
                FROM modelos_cartao WHERE id = NEW.modelo_cartao_id;

                IF proprietario_nucleo IS NULL OR modelo_status IS NULL THEN
                    RAISE EXCEPTION 'Proprietario ou modelo de cartao inexistente para prova.';
                END IF;

                IF modelo_status <> 'homologado' THEN
                    RAISE EXCEPTION 'Prova em rascunho exige modelo de cartao homologado.';
                END IF;

                IF modelo_nucleo IS NOT NULL AND modelo_nucleo <> proprietario_nucleo THEN
                    RAISE EXCEPTION 'Modelo de cartao pertence a outro nucleo.';
                END IF;

                IF NEW.quantidade_questoes <> modelo_questoes
                    OR NEW.quantidade_alternativas <> modelo_alternativas_quantidade
                    OR NEW.alternativas IS DISTINCT FROM modelo_alternativas THEN
                    RAISE EXCEPTION 'Quantidades e alternativas da prova devem corresponder ao modelo de cartao.';
                END IF;

                IF TG_OP = 'UPDATE'
                    AND NEW.quantidade_questoes < (SELECT COALESCE(MAX(numero), 0) FROM questoes WHERE prova_id = NEW.id) THEN
                    RAISE EXCEPTION 'Quantidade de questoes nao pode excluir numeros ja cadastrados.';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_validar_prova_modelo_cartao
            BEFORE INSERT OR UPDATE OF nucleo_id, escola_id, modelo_cartao_id, quantidade_questoes, quantidade_alternativas, alternativas
            ON provas
            FOR EACH ROW EXECUTE FUNCTION validar_prova_modelo_cartao();

            CREATE OR REPLACE FUNCTION validar_questao_prova_rascunho() RETURNS trigger AS $$
            DECLARE
                prova_status varchar;
                prova_quantidade smallint;
            BEGIN
                SELECT status, quantidade_questoes INTO prova_status, prova_quantidade
                FROM provas WHERE id = NEW.prova_id AND deleted_at IS NULL;

                IF prova_status IS NULL THEN
                    RAISE EXCEPTION 'Prova inexistente para questao.';
                END IF;

                IF prova_status <> 'rascunho' THEN
                    RAISE EXCEPTION 'Questoes somente podem ser alteradas em prova rascunho.';
                END IF;

                IF NEW.numero > prova_quantidade THEN
                    RAISE EXCEPTION 'Numero da questao excede a quantidade definida na prova.';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_validar_questao_prova_rascunho
            BEFORE INSERT OR UPDATE ON questoes
            FOR EACH ROW EXECUTE FUNCTION validar_questao_prova_rascunho();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_validar_questao_prova_rascunho ON questoes');
        DB::unprepared('DROP FUNCTION IF EXISTS validar_questao_prova_rascunho()');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_validar_prova_modelo_cartao ON provas');
        DB::unprepared('DROP FUNCTION IF EXISTS validar_prova_modelo_cartao()');
        Schema::dropIfExists('questoes');
        Schema::dropIfExists('provas');
    }
};
