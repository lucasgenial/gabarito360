<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arquivos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('disco', 40);
            $table->string('caminho', 500);
            $table->string('nome_original', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('tamanho_bytes');
            $table->string('checksum', 128);
            $table->string('classificacao', 40);
            $table->string('proprietario_tipo', 80);
            $table->uuid('proprietario_id')->nullable();
            $table->uuid('criado_por_id')->nullable();
            $table->dateTime('reter_ate', 6)->nullable();
            $table->dateTime('descartado_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('criado_por_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->unique(['disco', 'caminho'], 'uq_arquivos_disco_caminho');
            $table->index('checksum', 'idx_arquivos_checksum');
            $table->index(['classificacao', 'reter_ate'], 'idx_arquivos_classificacao_retencao');
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->uuid('foto_arquivo_id')->nullable()->after('telefone');
            $table->foreign('foto_arquivo_id')->references('id')->on('arquivos')->nullOnDelete();
        });

        Schema::table('alunos', function (Blueprint $table) {
            $table->string('nome_social', 180)->nullable()->after('nome');
            $table->uuid('foto_arquivo_id')->nullable()->after('documento');
            $table->foreign('foto_arquivo_id')->references('id')->on('arquivos')->nullOnDelete();
        });

        Schema::create('responsaveis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome', 180);
            $table->string('email', 254)->nullable();
            $table->string('telefone', 30)->nullable();
            $table->char('documento_hash', 64)->nullable()->unique('uq_responsaveis_documento_hash');
            $table->string('status', 20)->default('ativo');
            $table->timestamps(6);
            $table->index('nome', 'idx_responsaveis_nome');
        });

        Schema::create('aluno_responsaveis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('aluno_id');
            $table->uuid('responsavel_id');
            $table->string('parentesco', 50);
            $table->boolean('principal')->default(false);
            $table->boolean('autorizado_contato')->default(true);
            $table->date('inicio_em');
            $table->date('fim_em')->nullable();
            $table->timestamps(6);
            $table->foreign('aluno_id')->references('id')->on('alunos')->restrictOnDelete();
            $table->foreign('responsavel_id')->references('id')->on('responsaveis')->restrictOnDelete();
            $table->index(['aluno_id', 'fim_em'], 'idx_aluno_responsaveis_aluno_vigencia');
            $table->index(['responsavel_id', 'fim_em'], 'idx_aluno_responsaveis_responsavel_vigencia');
        });

        Schema::create('preferencias_usuario', function (Blueprint $table) {
            $table->uuid('usuario_id')->primary();
            $table->string('tema', 20)->default('claro');
            $table->string('densidade', 20)->default('confortavel');
            $table->boolean('contraste_alto')->default(false);
            $table->boolean('reduzir_movimento')->default(false);
            $table->string('idioma', 10)->default('pt-BR');
            $table->timestamps(6);
            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
        });

        Schema::create('preferencias_notificacao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id');
            $table->string('evento', 80);
            $table->string('canal', 30);
            $table->boolean('habilitada')->default(true);
            $table->timestamps(6);
            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->unique(['usuario_id', 'evento', 'canal'], 'uq_preferencias_notificacao');
        });

        Schema::create('solicitacoes_lgpd', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo', 40);
            $table->string('titular_tipo', 40);
            $table->char('titular_referencia_hash', 64);
            $table->uuid('solicitante_id')->nullable();
            $table->string('status', 30)->default('aberta');
            $table->text('descricao');
            $table->text('decisao')->nullable();
            $table->date('prazo_at');
            $table->dateTime('concluida_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('solicitante_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['status', 'prazo_at'], 'idx_solicitacoes_lgpd_status_prazo');
            $table->index('titular_referencia_hash', 'idx_solicitacoes_lgpd_titular');
        });

        DB::statement("ALTER TABLE responsaveis ADD CONSTRAINT ck_responsaveis_status CHECK (status IN ('ativo', 'inativo'))");
        DB::statement('ALTER TABLE aluno_responsaveis ADD CONSTRAINT ck_aluno_responsaveis_periodo CHECK (fim_em IS NULL OR fim_em >= inicio_em)');
        DB::statement("ALTER TABLE aluno_responsaveis ADD chave_vigente VARCHAR(255) AS (IF(fim_em IS NULL, CONCAT(CAST(aluno_id AS CHAR(36)), '|', CAST(responsavel_id AS CHAR(36))), NULL)) PERSISTENT");
        DB::statement('CREATE UNIQUE INDEX uq_aluno_responsaveis_vigente ON aluno_responsaveis (chave_vigente)');
        DB::statement("ALTER TABLE preferencias_usuario ADD CONSTRAINT ck_preferencias_tema CHECK (tema IN ('claro', 'escuro'))");
        DB::statement("ALTER TABLE preferencias_usuario ADD CONSTRAINT ck_preferencias_densidade CHECK (densidade IN ('compacta', 'confortavel'))");
        DB::statement("ALTER TABLE preferencias_notificacao ADD CONSTRAINT ck_preferencias_notificacao_canal CHECK (canal IN ('sistema', 'email', 'push'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_lgpd');
        Schema::dropIfExists('preferencias_notificacao');
        Schema::dropIfExists('preferencias_usuario');
        Schema::dropIfExists('aluno_responsaveis');
        Schema::dropIfExists('responsaveis');
        Schema::table('alunos', function (Blueprint $table) {
            $table->dropForeign(['foto_arquivo_id']);
            $table->dropColumn(['nome_social', 'foto_arquivo_id']);
        });
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['foto_arquivo_id']);
            $table->dropColumn('foto_arquivo_id');
        });
        Schema::dropIfExists('arquivos');
    }
};
