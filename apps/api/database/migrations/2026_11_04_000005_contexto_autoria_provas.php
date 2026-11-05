<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('provas', function (Blueprint $t) {
            $t->unsignedBigInteger('vinculo_criacao_id')->nullable();
            $t->string('autoria_origem', 30)->nullable();
            $t->foreign(['espaco_id', 'vinculo_criacao_id'], 'dc1_prova_autoria_vinculo')->references(['espaco_id', 'id'])->on('usuario_vinculos')->restrictOnDelete();
        });
        // Sem backfill: o perfil atual não prova qual era o contexto histórico.
        $campos = ['criado_por', 'perfil_criacao', 'vinculo_criacao_id', 'autoria_origem', 'espaco_id', 'escola_id'];
        if (DB::getDriverName() === 'sqlite') {
            $mudou = implode(' OR ', array_map(fn ($c) => "OLD.$c IS NOT NEW.$c", $campos));
            DB::unprepared("CREATE TRIGGER dc1_prova_autoria_imutavel BEFORE UPDATE ON provas WHEN OLD.autoria_origem IS NOT NULL AND ($mudou) BEGIN SELECT RAISE(ABORT, 'Contexto original da prova imutavel'); END");
        } else {
            $mudou = implode(' OR ', array_map(fn ($c) => "NOT (OLD.$c <=> NEW.$c)", $campos));
            DB::unprepared("CREATE TRIGGER dc1_prova_autoria_imutavel BEFORE UPDATE ON provas FOR EACH ROW BEGIN IF OLD.autoria_origem IS NOT NULL AND ($mudou) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Contexto original da prova imutavel'; END IF; END");
        }
    }

    public function down(): void { throw new RuntimeException('Preservar o contexto original das provas.'); }
};
