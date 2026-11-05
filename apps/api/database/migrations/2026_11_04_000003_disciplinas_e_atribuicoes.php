<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disciplinas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('espaco_id')->constrained('espacos')->restrictOnDelete();
            $t->string('nome', 100); $t->string('nome_chave', 100); $t->string('sigla', 20)->nullable();
            $t->string('referencia', 50)->nullable(); $t->string('referencia_edicao', 40)->nullable();
            $t->boolean('ativo')->default(true); $t->unsignedInteger('versao')->default(1); $t->timestamps();
            $t->unique(['espaco_id', 'id'], 'dc1_disciplina_escopo');
            $t->unique(['espaco_id', 'nome_chave'], 'dc1_disciplina_nome');
            $t->index(['espaco_id', 'ativo', 'nome']);
        });
        Schema::create('disciplina_professores', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('espaco_id'); $t->unsignedBigInteger('disciplina_id'); $t->unsignedBigInteger('vinculo_id');
            $t->boolean('ativo')->default(true); $t->unsignedInteger('versao')->default(1); $t->timestamps();
            $t->foreign(['espaco_id', 'disciplina_id'], 'dc1_ind_disciplina')->references(['espaco_id', 'id'])->on('disciplinas')->restrictOnDelete();
            $t->foreign(['espaco_id', 'vinculo_id'], 'dc1_ind_vinculo')->references(['espaco_id', 'id'])->on('usuario_vinculos')->restrictOnDelete();
            $t->unique(['espaco_id', 'disciplina_id', 'vinculo_id'], 'dc1_ind_unique');
            $t->index(['espaco_id', 'vinculo_id', 'ativo'], 'dc1_ind_docente');
        });
        Schema::create('turma_disciplinas', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('espaco_id'); $t->unsignedBigInteger('turma_id'); $t->unsignedBigInteger('disciplina_id');
            $t->boolean('ativo')->default(true);
            $t->unsignedInteger('versao')->default(1); $t->timestamps();
            $t->foreign(['espaco_id', 'turma_id'], 'dc1_comp_turma')->references(['espaco_id', 'id'])->on('turmas')->restrictOnDelete();
            $t->foreign(['espaco_id', 'disciplina_id'], 'dc1_comp_disciplina')->references(['espaco_id', 'id'])->on('disciplinas')->restrictOnDelete();
            $t->unique(['espaco_id', 'turma_id', 'disciplina_id'], 'dc1_comp_unique');
            $t->unique(['espaco_id', 'id'], 'dc1_comp_escopo');
        });
        Schema::create('docente_atribuicoes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('espaco_id'); $t->unsignedBigInteger('turma_disciplina_id'); $t->unsignedBigInteger('vinculo_id');
            $t->date('inicio_em'); $t->date('fim_em')->nullable(); $t->enum('papel', ['titular', 'codocencia', 'substituicao']);
            $t->enum('situacao', ['ativa', 'cancelada', 'encerrada'])->default('ativa');
            $t->unsignedInteger('versao')->default(1); $t->unsignedBigInteger('ator_vinculo_id'); $t->uuid('chave'); $t->char('pedido_hash', 64); $t->timestamps();
            $t->foreign(['espaco_id', 'turma_disciplina_id'], 'dc1_atr_comp')->references(['espaco_id', 'id'])->on('turma_disciplinas')->restrictOnDelete();
            $t->foreign(['espaco_id', 'vinculo_id'], 'dc1_atr_vinculo')->references(['espaco_id', 'id'])->on('usuario_vinculos')->restrictOnDelete();
            $t->foreign(['espaco_id', 'ator_vinculo_id'], 'dc1_atr_ator')->references(['espaco_id', 'id'])->on('usuario_vinculos')->restrictOnDelete();
            $t->unique(['espaco_id', 'ator_vinculo_id', 'chave'], 'dc1_atr_intencao');
            $t->unique(['espaco_id', 'id'], 'dc1_atr_escopo');
            $t->index(['turma_disciplina_id', 'situacao', 'inicio_em', 'fim_em'], 'dc1_atr_periodo');
        });
        Schema::create('disciplina_eventos', function (Blueprint $t) {
            $t->id(); $t->uuid('referencia')->unique(); $t->unsignedBigInteger('espaco_id'); $t->unsignedBigInteger('ator_vinculo_id');
            $t->string('entidade', 30); $t->unsignedBigInteger('entidade_id'); $t->string('acao', 40); $t->json('antes')->nullable(); $t->json('depois'); $t->timestamp('ocorrido_em');
            $t->foreign(['espaco_id', 'ator_vinculo_id'], 'dc1_evt_ator')->references(['espaco_id', 'id'])->on('usuario_vinculos')->restrictOnDelete();
            $t->index(['espaco_id', 'entidade', 'entidade_id'], 'dc1_evt_recurso');
        });
        foreach (['UPDATE', 'DELETE'] as $op) {
            $nome = 'dc1_eventos_'.strtolower($op);
            if (DB::getDriverName() === 'sqlite') DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON disciplina_eventos BEGIN SELECT RAISE(ABORT, 'Evento docente imutavel'); END");
            else DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON disciplina_eventos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Evento docente imutavel'");
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar o histórico pedagógico; recuperação exige procedimento próprio.');
    }
};
