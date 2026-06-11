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
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('nucleo_id')->nullable();
            $table->string('nome', 120);
            $table->integer('versao');
            $table->smallInteger('quantidade_questoes');
            $table->smallInteger('quantidade_alternativas');
            $table->jsonb('alternativas');
            $table->string('tipo_codigo', 30);
            $table->string('origem_codigo', 30);
            $table->jsonb('configuracao_omr');
            $table->char('artefato_checksum_sha256', 64)->nullable();
            $table->string('status', 20)->default('rascunho');
            $table->uuid('criado_por')->nullable();
            $table->uuid('homologado_por')->nullable();
            $table->timestampTz('homologado_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('nucleo_id')->references('id')->on('nucleos')->restrictOnDelete();
            $table->foreign('criado_por')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('homologado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_status CHECK (status IN ('rascunho', 'homologado', 'inativo'))");
        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_tipo_codigo CHECK (tipo_codigo IN ('sem_codigo', 'qr_code', 'codigo_barras', 'ocr_texto'))");
        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_origem_codigo CHECK (origem_codigo IN ('nenhum', 'externo', 'sistema_afixado'))");
        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_codigo_semantica CHECK ((tipo_codigo = 'sem_codigo' AND origem_codigo = 'nenhum') OR (tipo_codigo <> 'sem_codigo' AND origem_codigo <> 'nenhum'))");
        DB::statement('ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_versao CHECK (versao > 0)');
        DB::statement('ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_quantidades CHECK (quantidade_questoes > 0 AND quantidade_alternativas > 1)');
        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_alternativas_json CHECK (jsonb_typeof(alternativas) = 'array')");
        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_configuracao_json CHECK (jsonb_typeof(configuracao_omr) = 'object')");
        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_checksum CHECK (artefato_checksum_sha256 IS NULL OR artefato_checksum_sha256 ~ '^[0-9a-f]{64}$')");
        DB::statement("ALTER TABLE modelos_cartao ADD CONSTRAINT ck_modelos_cartao_homologacao CHECK ((status = 'rascunho' AND homologado_at IS NULL AND homologado_por IS NULL) OR (status = 'homologado' AND homologado_at IS NOT NULL AND homologado_por IS NOT NULL AND artefato_checksum_sha256 IS NOT NULL) OR (status = 'inativo' AND ((homologado_at IS NULL AND homologado_por IS NULL) OR (homologado_at IS NOT NULL AND homologado_por IS NOT NULL AND artefato_checksum_sha256 IS NOT NULL))))");
        DB::statement('CREATE UNIQUE INDEX uq_modelos_cartao_nucleo_nome_versao ON modelos_cartao (nucleo_id, lower(nome), versao) NULLS NOT DISTINCT');
        DB::statement('CREATE INDEX idx_modelos_cartao_nucleo_status ON modelos_cartao (nucleo_id, status)');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION proteger_modelo_cartao_versionado() RETURNS trigger AS $$
            BEGIN
                IF OLD.status IN ('homologado', 'inativo') AND (
                    NEW.nucleo_id IS DISTINCT FROM OLD.nucleo_id OR
                    NEW.nome IS DISTINCT FROM OLD.nome OR
                    NEW.versao IS DISTINCT FROM OLD.versao OR
                    NEW.quantidade_questoes IS DISTINCT FROM OLD.quantidade_questoes OR
                    NEW.quantidade_alternativas IS DISTINCT FROM OLD.quantidade_alternativas OR
                    NEW.alternativas IS DISTINCT FROM OLD.alternativas OR
                    NEW.tipo_codigo IS DISTINCT FROM OLD.tipo_codigo OR
                    NEW.origem_codigo IS DISTINCT FROM OLD.origem_codigo OR
                    NEW.configuracao_omr IS DISTINCT FROM OLD.configuracao_omr OR
                    NEW.artefato_checksum_sha256 IS DISTINCT FROM OLD.artefato_checksum_sha256 OR
                    NEW.criado_por IS DISTINCT FROM OLD.criado_por OR
                    NEW.homologado_por IS DISTINCT FROM OLD.homologado_por OR
                    NEW.homologado_at IS DISTINCT FROM OLD.homologado_at
                ) THEN
                    RAISE EXCEPTION 'Modelo de cartao homologado ou inativo e imutavel.';
                END IF;

                IF OLD.status = 'homologado' AND NEW.status NOT IN ('homologado', 'inativo') THEN
                    RAISE EXCEPTION 'Modelo homologado somente pode ser inativado.';
                END IF;

                IF OLD.status = 'inativo' AND NEW.status <> 'inativo' THEN
                    RAISE EXCEPTION 'Modelo inativo nao pode mudar de estado.';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_proteger_modelo_cartao_versionado
            BEFORE UPDATE ON modelos_cartao
            FOR EACH ROW EXECUTE FUNCTION proteger_modelo_cartao_versionado();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_proteger_modelo_cartao_versionado ON modelos_cartao');
        DB::unprepared('DROP FUNCTION IF EXISTS proteger_modelo_cartao_versionado()');
        Schema::dropIfExists('modelos_cartao');
    }
};
