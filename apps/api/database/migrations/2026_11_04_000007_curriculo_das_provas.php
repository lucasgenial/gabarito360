<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prova_curriculos', function (Blueprint $t) {
            $t->unsignedBigInteger('prova_id')->primary();
            $t->unsignedBigInteger('espaco_id');
            $t->unsignedInteger('versao')->default(1);
            $t->unsignedBigInteger('ator_vinculo_id');
            $t->dateTime('publicado_em')->nullable();
            $t->timestamps();
            $t->unique(['espaco_id','prova_id'],'dc1_curriculo_escopo');
            $t->foreign(['espaco_id','prova_id'])->references(['espaco_id','id'])->on('provas')->restrictOnDelete();
            $t->foreign(['espaco_id','ator_vinculo_id'])->references(['espaco_id','id'])->on('usuario_vinculos')->restrictOnDelete();
        });
        Schema::create('prova_disciplinas', function (Blueprint $t) {
            $t->unsignedBigInteger('espaco_id'); $t->unsignedBigInteger('prova_id'); $t->unsignedBigInteger('disciplina_id');
            $t->string('nome',100); $t->string('sigla',20)->nullable();
            $t->primary(['prova_id','disciplina_id']);
            $t->foreign(['espaco_id','prova_id'])->references(['espaco_id','prova_id'])->on('prova_curriculos')->restrictOnDelete();
            $t->foreign(['espaco_id','disciplina_id'])->references(['espaco_id','id'])->on('disciplinas')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar a configuração curricular e o histórico das provas.');
    }
};
