<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pedido_exportacao_artefatos', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('autorizacao_id')->unique()->constrained('pedido_exportacao_autorizacoes')->restrictOnDelete();
            $t->unsignedInteger('autorizacao_versao');
            $t->foreignId('acesso_id')->constrained('usuario_acessos_internos')->restrictOnDelete();
            $t->unsignedInteger('acesso_versao');
            $t->uuid('chave');
            $t->string('situacao',20);
            $t->longText('conteudo_cifrado')->nullable();
            $t->char('sha256_cifrado',64)->nullable();
            $t->dateTime('expira_em');
            $t->dateTime('created_at');
            $t->dateTime('updated_at');
            $t->unique(['acesso_id','acesso_versao','chave'],'exportacao_artefato_chave_unica');
        });
        Schema::create('pedido_exportacao_artefato_eventos', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignUuid('artefato_id')->constrained('pedido_exportacao_artefatos')->restrictOnDelete();
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();
            $t->string('acao',30);
            $t->dateTime('created_at');
        });
    }
    public function down(): void
    {
        throw new RuntimeException('L4-C3: preservar artefatos e auditoria; reversão exige procedimento próprio.');
    }
};
