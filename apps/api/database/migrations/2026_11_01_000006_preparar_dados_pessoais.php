<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario_vinculos', fn (Blueprint $t) => $t->enum('perfil', [
            'admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aplicador', 'aluno', 'professor_autonomo',
        ])->change());
        if (!collect(Schema::getIndexes('espacos'))->contains('name', 't3_espaco_tipo')) {
            Schema::table('espacos', fn (Blueprint $t) => $t->unique(['id', 'tipo'], 't3_espaco_tipo'));
        }
        foreach (['turmas', 'provas', 'alunos'] as $tabela) {
            if (!Schema::hasColumn($tabela, 'espaco_tipo')) {
                Schema::table($tabela, function (Blueprint $t) use ($tabela) {
                    $t->string('espaco_tipo', 24)->default('escola');
                });
            }
            if (!collect(Schema::getForeignKeys($tabela))->contains('name', 't3_'.$tabela.'_tipo')) {
                Schema::table($tabela, fn (Blueprint $t) => $t->foreign(['espaco_id', 'espaco_tipo'], 't3_'.$tabela.'_tipo')->references(['id', 'tipo'])->on('espacos')->restrictOnDelete()->restrictOnUpdate());
            }
            if ($tabela !== 'alunos') Schema::table($tabela, fn (Blueprint $t) => $t->unsignedBigInteger('escola_id')->nullable()->change());
            $regra = $tabela === 'alunos'
                ? "NEW.espaco_tipo = 'escola' OR (NEW.espaco_tipo = 'pessoal' AND NEW.espaco_id IS NOT NULL AND NEW.cpf IS NULL AND NEW.cpf_hash IS NULL AND NEW.data_nascimento IS NULL AND NEW.genero IS NULL AND NEW.nome_responsavel IS NULL AND NEW.foto_path IS NULL)"
                : "(NEW.espaco_tipo = 'escola' AND NEW.escola_id IS NOT NULL) OR (NEW.espaco_tipo = 'pessoal' AND NEW.escola_id IS NULL AND NEW.espaco_id IS NOT NULL)";
            foreach (['INSERT', 'UPDATE'] as $evento) {
                $nome = 't3_'.$tabela.'_'.strtolower($evento);
                DB::unprepared("DROP TRIGGER IF EXISTS $nome");
                if (DB::getDriverName() === 'sqlite') {
                    DB::unprepared("CREATE TRIGGER $nome BEFORE $evento ON $tabela WHEN NOT ($regra) BEGIN SELECT RAISE(ABORT, 'Dados incompatíveis com tipo de espaço'); END");
                } else {
                    DB::unprepared("CREATE TRIGGER $nome BEFORE $evento ON $tabela FOR EACH ROW BEGIN IF NOT ($regra) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dados incompativeis com tipo de espaco'; END IF; END");
                }
            }
        }
        if (!Schema::hasColumn('cartoes', 'turma_aplicacao_id')) {
            Schema::table('cartoes', function (Blueprint $t) {
                $t->unsignedBigInteger('turma_aplicacao_id')->nullable();
            });
        }
        if (!collect(Schema::getForeignKeys('cartoes'))->contains('name', 't3_cartao_turma')) {
            Schema::table('cartoes', fn (Blueprint $t) => $t->foreign(['espaco_id', 'turma_aplicacao_id'], 't3_cartao_turma')->references(['espaco_id', 'id'])->on('turmas')->restrictOnDelete()->restrictOnUpdate());
        }
        // Sem backfill inferido: cartões existentes são fixados sob lock ao mover o aluno.
        // O catálogo pessoal é preparado; o resolvedor permanece desligado por padrão.
        Artisan::call('g360:permissoes-sincronizar');
    }

    public function down(): void
    {
        throw new RuntimeException('T3: não remover dados pessoais ou histórico por rollback automático.');
    }
};
