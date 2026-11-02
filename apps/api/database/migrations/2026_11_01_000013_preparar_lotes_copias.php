<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copias_lotes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('chave')->unique();
            $t->foreignId('origem_espaco_id')->constrained('espacos')->restrictOnDelete();
            $t->foreignId('destino_espaco_id')->constrained('espacos')->restrictOnDelete();
            $t->foreignId('solicitante_id')->constrained('usuarios')->restrictOnDelete();
            $t->foreignId('solicitante_vinculo_id')->constrained('usuario_vinculos')->restrictOnDelete();
            $t->foreign(['solicitante_id','solicitante_vinculo_id'], 't5_solicitante_vinculo')
                ->references(['usuario_id','id'])->on('usuario_vinculos')->restrictOnDelete()->restrictOnUpdate();
            $t->enum('finalidade', ['preparacao_material']);
            $t->string('politica', 40);
            $t->char('pedido_digest', 64);
            $t->char('selecao_digest', 64);
            $t->enum('estado', ['aguardando_autorizacao', 'aguardando_auditoria', 'bloqueado']);
            $t->unsignedInteger('total_itens');
            $t->timestamps();
            $t->unique(['id', 'origem_espaco_id', 'destino_espaco_id'], 't5_lote_espacos');
        });
        Schema::create('copias_itens', function (Blueprint $t) {
            $t->id();
            $t->uuid('lote_id');
            $t->unsignedBigInteger('origem_espaco_id');
            $t->unsignedBigInteger('destino_espaco_id');
            $t->enum('entidade', ['prova']);
            $t->unsignedBigInteger('origem_id');
            $t->char('fonte_digest', 64);
            $t->enum('estado', ['selecionado'])->default('selecionado');
            $t->unique(['lote_id', 'entidade', 'origem_id'], 't5_item_origem');
            $t->unique(['lote_id','entidade','origem_id','origem_espaco_id','destino_espaco_id'], 't5_item_escopo');
            $t->foreign(['lote_id','origem_espaco_id','destino_espaco_id'], 't5_item_lote')
                ->references(['id','origem_espaco_id','destino_espaco_id'])->on('copias_lotes')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::create('copias_autorizacoes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('lote_id');
            $t->unsignedBigInteger('origem_espaco_id');
            $t->unsignedBigInteger('destino_espaco_id');
            $t->enum('lado', ['origem', 'destino']);
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();
            $t->foreignId('vinculo_id')->constrained('usuario_vinculos')->restrictOnDelete();
            $t->foreign(['ator_id','vinculo_id'], 't5_decisao_ator')
                ->references(['usuario_id','id'])->on('usuario_vinculos')->restrictOnDelete()->restrictOnUpdate();
            $t->char('versao_acesso', 64);
            $t->char('selecao_digest', 64);
            $t->dateTime('valida_ate');
            $t->dateTime('revogada_em')->nullable();
            $t->foreignId('revogada_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $t->unsignedBigInteger('revogada_vinculo_id')->nullable();
            $t->char('revogada_versao_acesso',64)->nullable();
            $t->foreign(['revogada_por','revogada_vinculo_id'], 't5_revogacao_ator')
                ->references(['usuario_id','id'])->on('usuario_vinculos')->restrictOnDelete()->restrictOnUpdate();
            $t->dateTime('created_at');
            $t->unique(['lote_id','lado'], 't5_decisao_lado');
            $t->foreign(['lote_id','origem_espaco_id','destino_espaco_id'], 't5_decisao_lote')
                ->references(['id','origem_espaco_id','destino_espaco_id'])->on('copias_lotes')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::create('registros_origem', function (Blueprint $t) {
            $t->id();
            $t->uuid('lote_id');
            $t->enum('entidade', ['prova']);
            $t->unsignedBigInteger('origem_espaco_id');
            $t->unsignedBigInteger('origem_id');
            $t->unsignedBigInteger('destino_espaco_id');
            $t->unsignedBigInteger('destino_id');
            $t->foreignId('copiado_por')->constrained('usuarios')->restrictOnDelete();
            $t->dateTime('created_at');
            $t->unique(['origem_espaco_id','entidade','origem_id','destino_espaco_id'], 't5_mapa_origem');
            $t->unique(['destino_espaco_id','entidade','destino_id'], 't5_mapa_destino');
            $t->foreign(['lote_id','entidade','origem_id','origem_espaco_id','destino_espaco_id'], 't5_mapa_item')
                ->references(['lote_id','entidade','origem_id','origem_espaco_id','destino_espaco_id'])
                ->on('copias_itens')->restrictOnDelete()->restrictOnUpdate();
        });
        if (in_array(DB::connection()->getDriverName(), ['mysql','mariadb'], true)) {
            DB::statement('ALTER TABLE copias_lotes ADD CONSTRAINT t5_espacos_distintos CHECK (origem_espaco_id <> destino_espaco_id)');
            DB::statement('ALTER TABLE copias_lotes ADD CONSTRAINT t5_total_itens CHECK (total_itens BETWEEN 1 AND 100)');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('T5: preservar decisões e proveniência; reversão exige procedimento próprio.');
    }
};
