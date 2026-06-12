<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aplicacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prova_id');
            $table->uuid('turma_id');
            $table->uuid('escola_id');
            $table->uuid('gabarito_oficial_id');
            $table->string('titulo', 180);
            $table->dateTime('inicio_previsto_at', 6)->nullable();
            $table->dateTime('fim_previsto_at', 6)->nullable();
            $table->dateTime('iniciada_at', 6)->nullable();
            $table->dateTime('finalizada_at', 6)->nullable();
            $table->string('status', 30)->default('rascunho');
            $table->uuid('criada_por_id')->nullable();
            $table->timestamps(6);
            $table->foreign('prova_id')->references('id')->on('provas')->restrictOnDelete();
            $table->foreign('turma_id')->references('id')->on('turmas')->restrictOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->foreign('gabarito_oficial_id')->references('id')->on('gabaritos_oficiais')->restrictOnDelete();
            $table->foreign('criada_por_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['turma_id', 'status', 'inicio_previsto_at'], 'idx_aplicacoes_turma_status_inicio');
            $table->index(['prova_id', 'status'], 'idx_aplicacoes_prova_status');
            $table->index(['escola_id', 'status'], 'idx_aplicacoes_escola_status');
        });

        Schema::create('aplicacao_aplicadores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('aplicacao_id');
            $table->uuid('usuario_id');
            $table->string('papel', 30);
            $table->dateTime('inicio_at', 6)->useCurrent();
            $table->dateTime('fim_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('aplicacao_id')->references('id')->on('aplicacoes')->cascadeOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->index(['aplicacao_id', 'fim_at'], 'idx_aplicacao_aplicadores_aplicacao');
            $table->index(['usuario_id', 'fim_at'], 'idx_aplicacao_aplicadores_usuario');
        });

        Schema::create('aplicacao_alunos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('aplicacao_id');
            $table->uuid('aluno_id');
            $table->uuid('matricula_turma_id');
            $table->string('status', 30)->default('previsto');
            $table->dateTime('confirmado_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('aplicacao_id')->references('id')->on('aplicacoes')->cascadeOnDelete();
            $table->foreign('aluno_id')->references('id')->on('alunos')->restrictOnDelete();
            $table->foreign('matricula_turma_id')->references('id')->on('matriculas_turmas')->restrictOnDelete();
            $table->unique(['aplicacao_id', 'aluno_id'], 'uq_aplicacao_alunos_aplicacao_aluno');
            $table->index(['aplicacao_id', 'status'], 'idx_aplicacao_alunos_status');
            $table->index(['aluno_id', 'aplicacao_id'], 'idx_aplicacao_alunos_aluno_aplicacao');
        });

        Schema::create('cartoes_resposta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prova_id');
            $table->uuid('aluno_id');
            $table->uuid('aplicacao_id');
            $table->string('codigo_impresso', 120)->nullable();
            $table->string('codigo_impresso_normalizado', 120)->nullable();
            $table->string('codigo_sistema', 19)->nullable()->unique('uq_cartoes_codigo_sistema');
            $table->boolean('codigo_sistema_afixado')->default(false);
            $table->string('motivo_sem_codigo_impresso', 80)->nullable();
            $table->string('status', 30)->default('vigente');
            $table->timestamps(6);
            $table->foreign('prova_id')->references('id')->on('provas')->restrictOnDelete();
            $table->foreign('aluno_id')->references('id')->on('alunos')->restrictOnDelete();
            $table->foreign('aplicacao_id')->references('id')->on('aplicacoes')->restrictOnDelete();
            $table->unique(['prova_id', 'codigo_impresso_normalizado'], 'uq_cartoes_prova_codigo_impresso');
        });

        Schema::create('leituras_cartao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('aplicacao_id');
            $table->uuid('aplicacao_aluno_id');
            $table->uuid('cartao_resposta_id')->nullable();
            $table->uuid('modelo_cartao_id');
            $table->uuid('arquivo_original_id');
            $table->uuid('arquivo_processado_id')->nullable();
            $table->uuid('capturada_por_id');
            $table->uuid('dispositivo_id')->nullable();
            $table->string('operacao_id', 100)->unique('uq_leituras_operacao');
            $table->string('status', 30)->default('recebida');
            $table->decimal('confianca_geral', 6, 5)->nullable();
            $table->boolean('requer_revisao')->default(false);
            $table->json('alertas')->nullable();
            $table->dateTime('confirmada_at', 6)->nullable();
            $table->dateTime('cancelada_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('aplicacao_id')->references('id')->on('aplicacoes')->restrictOnDelete();
            $table->foreign('aplicacao_aluno_id')->references('id')->on('aplicacao_alunos')->restrictOnDelete();
            $table->foreign('cartao_resposta_id')->references('id')->on('cartoes_resposta')->restrictOnDelete();
            $table->foreign('modelo_cartao_id')->references('id')->on('modelos_cartao')->restrictOnDelete();
            $table->foreign('arquivo_original_id')->references('id')->on('arquivos')->restrictOnDelete();
            $table->foreign('arquivo_processado_id')->references('id')->on('arquivos')->restrictOnDelete();
            $table->foreign('capturada_por_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('dispositivo_id')->references('id')->on('dispositivos_mobile')->nullOnDelete();
            $table->index(['aplicacao_id', 'status', 'created_at'], 'idx_leituras_aplicacao_status_data');
            $table->index(['aplicacao_aluno_id', 'created_at'], 'idx_leituras_aplicacao_aluno_data');
        });

        Schema::create('respostas_detectadas', function (Blueprint $table) {
            $table->id();
            $table->uuid('leitura_cartao_id');
            $table->uuid('questao_id');
            $table->string('alternativa_detectada', 10)->nullable();
            $table->string('alternativa_final', 10)->nullable();
            $table->string('tipo_deteccao', 30);
            $table->decimal('confianca', 6, 5)->nullable();
            $table->boolean('alterada_manualmente')->default(false);
            $table->string('motivo_alteracao', 500)->nullable();
            $table->uuid('alterada_por_id')->nullable();
            $table->dateTime('alterada_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('leitura_cartao_id')->references('id')->on('leituras_cartao')->cascadeOnDelete();
            $table->foreign('questao_id')->references('id')->on('questoes')->restrictOnDelete();
            $table->foreign('alterada_por_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->unique(['leitura_cartao_id', 'questao_id'], 'uq_respostas_detectadas_leitura_questao');
            $table->index(['questao_id', 'tipo_deteccao'], 'idx_respostas_detectadas_questao_tipo');
        });

        Schema::create('resultados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('aplicacao_id');
            $table->uuid('aplicacao_aluno_id');
            $table->uuid('aluno_id');
            $table->uuid('prova_id');
            $table->uuid('gabarito_oficial_id');
            $table->uuid('leitura_cartao_id');
            $table->unsignedSmallInteger('versao');
            $table->string('status', 30)->default('vigente');
            $table->unsignedSmallInteger('acertos')->default(0);
            $table->unsignedSmallInteger('erros')->default(0);
            $table->unsignedSmallInteger('brancos')->default(0);
            $table->unsignedSmallInteger('duplas')->default(0);
            $table->unsignedSmallInteger('anuladas')->default(0);
            $table->decimal('pontuacao', 12, 4)->default(0);
            $table->decimal('nota_percentual', 7, 4)->default(0);
            $table->dateTime('calculado_at', 6);
            $table->timestamps(6);
            $table->foreign('aplicacao_id')->references('id')->on('aplicacoes')->restrictOnDelete();
            $table->foreign('aplicacao_aluno_id')->references('id')->on('aplicacao_alunos')->restrictOnDelete();
            $table->foreign('aluno_id')->references('id')->on('alunos')->restrictOnDelete();
            $table->foreign('prova_id')->references('id')->on('provas')->restrictOnDelete();
            $table->foreign('gabarito_oficial_id')->references('id')->on('gabaritos_oficiais')->restrictOnDelete();
            $table->foreign('leitura_cartao_id')->references('id')->on('leituras_cartao')->restrictOnDelete();
            $table->unique(['aplicacao_aluno_id', 'versao'], 'uq_resultados_aplicacao_aluno_versao');
            $table->index(['aplicacao_id', 'status'], 'idx_resultados_aplicacao_status');
            $table->index(['aluno_id', 'prova_id', 'status'], 'idx_resultados_aluno_prova_status');
        });

        Schema::create('resultado_questoes', function (Blueprint $table) {
            $table->id();
            $table->uuid('resultado_id');
            $table->uuid('questao_id');
            $table->string('resposta_final', 10)->nullable();
            $table->string('situacao', 30);
            $table->decimal('pontuacao', 12, 4)->default(0);
            $table->json('tema_snapshot')->nullable();
            $table->timestamps(6);
            $table->foreign('resultado_id')->references('id')->on('resultados')->cascadeOnDelete();
            $table->foreign('questao_id')->references('id')->on('questoes')->restrictOnDelete();
            $table->unique(['resultado_id', 'questao_id'], 'uq_resultado_questoes_resultado_questao');
            $table->index(['questao_id', 'situacao'], 'idx_resultado_questoes_questao_situacao');
        });

        Schema::table('aplicacao_alunos', function (Blueprint $table) {
            $table->uuid('resultado_vigente_id')->nullable()->after('status');
            $table->foreign('resultado_vigente_id')->references('id')->on('resultados')->nullOnDelete();
        });

        Schema::create('relatorios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo', 50);
            $table->string('formato', 20);
            $table->uuid('solicitante_id');
            $table->uuid('arquivo_id')->nullable();
            $table->json('filtros');
            $table->json('escopo');
            $table->string('status', 30)->default('solicitado');
            $table->dateTime('solicitado_at', 6)->useCurrent();
            $table->dateTime('concluido_at', 6)->nullable();
            $table->dateTime('expira_at', 6)->nullable();
            $table->string('erro_codigo', 80)->nullable();
            $table->timestamps(6);
            $table->foreign('solicitante_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('arquivo_id')->references('id')->on('arquivos')->nullOnDelete();
            $table->index(['solicitante_id', 'status', 'solicitado_at'], 'idx_relatorios_solicitante_status_data');
            $table->index(['status', 'expira_at'], 'idx_relatorios_status_expiracao');
        });

        Schema::create('logs_sincronizacao', function (Blueprint $table) {
            $table->id();
            $table->string('operacao_id', 100)->unique('uq_logs_sincronizacao_operacao');
            $table->uuid('usuario_id')->nullable();
            $table->uuid('dispositivo_id')->nullable();
            $table->uuid('aplicacao_id')->nullable();
            $table->string('tipo', 80);
            $table->string('status', 30);
            $table->unsignedSmallInteger('tentativas')->default(0);
            $table->char('payload_hash', 64);
            $table->string('erro_codigo', 80)->nullable();
            $table->string('erro_detalhe_sanitizado', 500)->nullable();
            $table->dateTime('processado_at', 6)->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('dispositivo_id')->references('id')->on('dispositivos_mobile')->nullOnDelete();
            $table->foreign('aplicacao_id')->references('id')->on('aplicacoes')->nullOnDelete();
            $table->index(['status', 'created_at'], 'idx_logs_sincronizacao_status_data');
            $table->index(['dispositivo_id', 'created_at'], 'idx_logs_sincronizacao_dispositivo_data');
        });

        DB::statement("ALTER TABLE aplicacoes ADD CONSTRAINT ck_aplicacoes_status CHECK (status IN ('rascunho', 'agendada', 'em_andamento', 'finalizada', 'cancelada'))");
        DB::statement('ALTER TABLE aplicacoes ADD CONSTRAINT ck_aplicacoes_periodo CHECK (fim_previsto_at IS NULL OR inicio_previsto_at IS NULL OR fim_previsto_at >= inicio_previsto_at)');
        DB::statement("ALTER TABLE aplicacao_aplicadores ADD chave_vigente VARCHAR(255) AS (IF(fim_at IS NULL, CONCAT(CAST(aplicacao_id AS CHAR(36)), '|', CAST(usuario_id AS CHAR(36)), '|', papel), NULL)) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_aplicacao_aplicadores_vigente ON aplicacao_aplicadores (chave_vigente)');
        DB::statement("ALTER TABLE cartoes_resposta ADD chave_vigente VARCHAR(255) AS (IF(status = 'vigente', CONCAT(CAST(prova_id AS CHAR(36)), '|', CAST(aluno_id AS CHAR(36))), NULL)) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_cartoes_resposta_vigente ON cartoes_resposta (chave_vigente)');
        DB::statement("ALTER TABLE resultados ADD chave_vigente VARCHAR(255) AS (IF(status = 'vigente', CAST(aplicacao_aluno_id AS CHAR(36)), NULL)) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_resultados_vigente ON resultados (chave_vigente)');
        DB::statement('ALTER TABLE resultados ADD CONSTRAINT ck_resultados_nota CHECK (nota_percentual BETWEEN 0 AND 100)');
        DB::statement("ALTER TABLE relatorios ADD CONSTRAINT ck_relatorios_formato CHECK (formato IN ('csv', 'pdf'))");
        DB::statement("ALTER TABLE relatorios ADD CONSTRAINT ck_relatorios_status CHECK (status IN ('solicitado', 'processando', 'concluido', 'falhou', 'expirado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_sincronizacao');
        Schema::dropIfExists('relatorios');
        Schema::table('aplicacao_alunos', function (Blueprint $table) {
            $table->dropForeign(['resultado_vigente_id']);
            $table->dropColumn('resultado_vigente_id');
        });
        Schema::dropIfExists('resultado_questoes');
        Schema::dropIfExists('resultados');
        Schema::dropIfExists('respostas_detectadas');
        Schema::dropIfExists('leituras_cartao');
        Schema::dropIfExists('cartoes_resposta');
        Schema::dropIfExists('aplicacao_alunos');
        Schema::dropIfExists('aplicacao_aplicadores');
        Schema::dropIfExists('aplicacoes');
    }
};
