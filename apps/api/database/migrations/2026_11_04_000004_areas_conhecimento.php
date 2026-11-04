<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('areas_conhecimento', function (Blueprint $t) {
            $t->id(); $t->foreignId('espaco_id')->constrained('espacos')->restrictOnDelete();
            $t->string('nome', 100); $t->string('nome_chave', 100); $t->string('sigla', 20)->nullable();
            $t->boolean('ativo')->default(true); $t->unsignedInteger('versao')->default(1); $t->timestamps();
            $t->unique(['espaco_id', 'id'], 'dc1_area_escopo');
            $t->unique(['espaco_id', 'nome_chave'], 'dc1_area_nome');
            $t->index(['espaco_id', 'ativo', 'nome'], 'dc1_area_busca');
        });
        Schema::create('disciplina_areas', function (Blueprint $t) {
            $t->unsignedBigInteger('espaco_id'); $t->unsignedBigInteger('disciplina_id'); $t->unsignedBigInteger('area_id');
            $t->primary(['espaco_id', 'disciplina_id', 'area_id'], 'dc1_disciplina_area');
            $t->foreign(['espaco_id', 'disciplina_id'], 'dc1_area_disciplina')->references(['espaco_id', 'id'])->on('disciplinas')->restrictOnDelete();
            $t->foreign(['espaco_id', 'area_id'], 'dc1_area_catalogo')->references(['espaco_id', 'id'])->on('areas_conhecimento')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar a organização pedagógica e seu histórico; recuperação exige procedimento próprio.');
    }
};
