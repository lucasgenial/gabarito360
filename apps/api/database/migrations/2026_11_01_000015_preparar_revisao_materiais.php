<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copias_revisoes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('lote_id')->unique()->constrained('copias_lotes')->restrictOnDelete();
            $t->unsignedBigInteger('ator_id');
            $t->unsignedBigInteger('vinculo_id');
            $t->foreign(['ator_id','vinculo_id'])->references(['usuario_id','id'])
                ->on('usuario_vinculos')->restrictOnDelete()->restrictOnUpdate();
            $t->char('versao_acesso',64);
            $t->char('selecao_digest',64);
            $t->char('conteudo_digest',64);
            $t->string('politica',32);
            $t->enum('classificacao',['material_sem_dados_pessoais']);
            $t->dateTime('created_at');
        });
    }
    public function down(): void { throw new RuntimeException('Preservar revisão de material e auditoria.'); }
};
