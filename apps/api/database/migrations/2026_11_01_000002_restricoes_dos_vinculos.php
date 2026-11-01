<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['turmas' => 'turma_id', 'alunos' => 'aluno_id'] as $pai => $campo) {
            // Mesmo índice previsto no T2-E; no servidor já existe, em instalação nova ainda não.
            $nome = 't2_'.$pai.'_espaco_id_id';
            $indice = collect(Schema::getIndexes($pai))->firstWhere('name', $nome);
            if ($indice && (!$indice['unique'] || $indice['columns'] !== ['espaco_id', 'id'])) {
                throw new RuntimeException('Índice de propriedade divergente: '.$nome);
            }
            if (!$indice) Schema::table($pai, fn (Blueprint $t) => $t->unique(['espaco_id', 'id'], $nome));
            $tabela = 'vinculo_'.$pai;
            if (Schema::hasTable($tabela)) continue;
            Schema::create($tabela, function (Blueprint $t) use ($pai, $campo) {
                $t->unsignedBigInteger('vinculo_id');
                $t->unsignedBigInteger('espaco_id');
                $t->unsignedBigInteger($campo);
                $t->primary(['vinculo_id', $campo]);
                $t->foreign(['espaco_id', 'vinculo_id'])->references(['espaco_id', 'id'])
                    ->on('usuario_vinculos')->restrictOnDelete()->restrictOnUpdate();
                $t->foreign(['espaco_id', $campo])->references(['espaco_id', 'id'])
                    ->on($pai)->restrictOnDelete()->restrictOnUpdate();
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('V1: reversão exige plano aprovado; não remover restrições automaticamente.');
    }
};
