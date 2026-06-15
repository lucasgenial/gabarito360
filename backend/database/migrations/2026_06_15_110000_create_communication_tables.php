<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelas de B7 (comunicação e tempo real):
 *
 * - `notificacoes`: notificações por usuário (sino do shell), com escopo;
 * - `eventos_agenda`: eventos/visitas/aplicações da agenda, escopados;
 * - `participantes_eventos`: participação e confirmação por usuário;
 * - `atividades_recentes`: feed de atividade dos painéis (append-only).
 *
 * `preferencias_notificacao` já existe desde a fundação de pessoas/preferências.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuario_id');
            $table->string('tipo', 80);
            $table->string('titulo', 180);
            $table->text('mensagem');
            $table->json('dados')->nullable();
            $table->string('link', 255)->nullable();
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->dateTime('lida_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->nullOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->nullOnDelete();
            $table->index(['usuario_id', 'lida_at', 'created_at'], 'idx_notificacoes_usuario_lida_data');
        });

        Schema::create('eventos_agenda', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo', 40);
            $table->string('titulo', 180);
            $table->text('descricao')->nullable();
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->uuid('turma_id')->nullable();
            $table->uuid('prova_id')->nullable();
            $table->uuid('aplicacao_id')->nullable();
            $table->string('local', 180)->nullable();
            $table->dateTime('inicio_at', 6);
            $table->dateTime('fim_at', 6)->nullable();
            $table->string('status', 30)->default('agendado');
            $table->uuid('criado_por_id')->nullable();
            $table->timestamps(6);
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->nullOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->nullOnDelete();
            $table->foreign('turma_id')->references('id')->on('turmas')->nullOnDelete();
            $table->foreign('prova_id')->references('id')->on('provas')->nullOnDelete();
            $table->foreign('aplicacao_id')->references('id')->on('aplicacoes')->nullOnDelete();
            $table->foreign('criado_por_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['escola_id', 'inicio_at'], 'idx_eventos_agenda_escola_inicio');
            $table->index(['nucleo_id', 'inicio_at'], 'idx_eventos_agenda_nucleo_inicio');
            $table->index(['turma_id', 'inicio_at'], 'idx_eventos_agenda_turma_inicio');
        });

        Schema::create('participantes_eventos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('evento_id');
            $table->uuid('usuario_id');
            $table->string('papel', 30)->default('convidado');
            $table->string('status', 30)->default('convidado');
            $table->dateTime('respondido_at', 6)->nullable();
            $table->timestamps(6);
            $table->foreign('evento_id')->references('id')->on('eventos_agenda')->cascadeOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->unique(['evento_id', 'usuario_id'], 'uq_participantes_eventos_evento_usuario');
            $table->index(['usuario_id', 'status'], 'idx_participantes_eventos_usuario_status');
        });

        Schema::create('atividades_recentes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo', 60);
            $table->string('descricao', 255);
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->uuid('ator_id')->nullable();
            $table->string('sujeito_tipo', 80)->nullable();
            $table->uuid('sujeito_id')->nullable();
            $table->json('dados')->nullable();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->nullOnDelete();
            $table->foreign('escola_id')->references('id')->on('escolas')->nullOnDelete();
            $table->foreign('ator_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['escola_id', 'created_at'], 'idx_atividades_escola_data');
            $table->index(['nucleo_id', 'created_at'], 'idx_atividades_nucleo_data');
        });

        DB::statement("ALTER TABLE eventos_agenda ADD CONSTRAINT ck_eventos_agenda_status CHECK (status IN ('agendado', 'confirmado', 'cancelado', 'concluido'))");
        DB::statement('ALTER TABLE eventos_agenda ADD CONSTRAINT ck_eventos_agenda_periodo CHECK (fim_at IS NULL OR fim_at >= inicio_at)');
        DB::statement("ALTER TABLE participantes_eventos ADD CONSTRAINT ck_participantes_eventos_status CHECK (status IN ('convidado', 'confirmado', 'recusado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('atividades_recentes');
        Schema::dropIfExists('participantes_eventos');
        Schema::dropIfExists('eventos_agenda');
        Schema::dropIfExists('notificacoes');
    }
};
