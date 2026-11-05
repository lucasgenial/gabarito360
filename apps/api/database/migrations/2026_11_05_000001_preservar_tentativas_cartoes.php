<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartoes', function (Blueprint $t) {
            $t->json('qualidade')->nullable();
            $t->uuid('tentativa_id')->nullable()->index();
        });
        Schema::create('cartao_tentativas', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->unsignedBigInteger('espaco_id')->nullable();
            $t->unsignedBigInteger('prova_id');
            $t->unsignedBigInteger('aluno_id')->nullable();
            $t->unsignedBigInteger('origem_cartao_id')->unique();
            $t->unsignedInteger('numero');
            $t->char('contexto_chave', 64);
            $t->unique(['contexto_chave', 'numero'], 'cartao_tentativa_numero_unico');
            $t->unsignedBigInteger('autor_id')->nullable();
            $t->unsignedBigInteger('vinculo_id')->nullable();
            $t->dateTime('ocorrido_em', 6);
            $t->string('imagem_path', 500)->nullable();
            $t->char('imagem_sha256', 64)->nullable();
            $t->longText('dados');
            $t->unsignedBigInteger('anterior_cartao_id')->nullable();
            $t->char('anterior_fingerprint', 64)->nullable();
            $t->index(['espaco_id', 'prova_id', 'aluno_id', 'numero'], 'cartao_tentativa_contexto');
        });
        Schema::create('cartao_tentativa_eventos', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->uuid('tentativa_id');
            $t->string('acao', 30);
            $t->unsignedBigInteger('cartao_id')->nullable();
            $t->unsignedBigInteger('ator_id')->nullable();
            $t->unsignedBigInteger('vinculo_id')->nullable();
            $t->dateTime('ocorrido_em', 6);
            $t->string('motivo', 500)->nullable();
            $t->longText('dados');
            $t->char('sha256', 64);
            $t->char('anterior_sha256', 64)->nullable();
            $t->foreign('tentativa_id')->references('id')->on('cartao_tentativas')->restrictOnDelete();
            $t->index(['tentativa_id', 'id']);
        });
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        foreach (['cartao_tentativas', 'cartao_tentativa_eventos'] as $tabela) {
            foreach (['UPDATE', 'DELETE'] as $acao) {
                $nome = $tabela.'_'.strtolower($acao);
                DB::unprepared($sqlite ? "CREATE TRIGGER $nome BEFORE $acao ON $tabela BEGIN SELECT RAISE(ABORT,'Historico de cartao imutavel'); END"
                    : "CREATE TRIGGER $nome BEFORE $acao ON $tabela FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Historico de cartao imutavel'");
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar tentativas e evidências dos cartões.');
    }
};
