<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_geografias', function (Blueprint $t) {
            $t->char('id', 64)->primary();
            $t->char('ufs_sha256', 64); $t->char('municipios_sha256', 64);
            $t->longText('snapshot'); $t->char('snapshot_sha256', 64); $t->dateTime('created_at');
        });
        Schema::create('catalogo_importacoes', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->char('chave_ingestao', 64)->unique();
            $t->char('geografia_id', 64);
            $t->foreign('geografia_id')->references('id')->on('catalogo_geografias')->restrictOnDelete();
            $t->string('fonte', 20); $t->unsignedSmallInteger('ano'); $t->string('schema_versao', 40);
            $t->char('arquivo_sha256', 64); $t->longText('manifesto');
            $t->string('estado', 20); $t->unsignedInteger('total_esperado');
            $t->unsignedInteger('cursor')->default(0);
            $t->uuid('lease_token')->nullable(); $t->dateTime('lease_ate')->nullable();
            $t->dateTime('validado_em')->nullable(); $t->timestamps();
            $t->index(['fonte','ano','estado'], 'catalogo_importacoes_edicao_idx');
        });
        Schema::create('catalogo_observacoes', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignUuid('importacao_id')->constrained('catalogo_importacoes')->restrictOnDelete();
            $t->unsignedInteger('ordem'); $t->string('chave_fonte', 80);
            $t->longText('payload'); $t->char('payload_sha256', 64); $t->dateTime('created_at');
            $t->unique(['importacao_id','chave_fonte'], 'catalogo_observacoes_fonte_unique');
            $t->unique(['importacao_id','ordem'], 'catalogo_observacoes_ordem_unique');
        });
        foreach (['catalogo_geografias','catalogo_observacoes'] as $tabela) {
            foreach (['UPDATE','DELETE'] as $acao) {
                $nome = $tabela.'_imutavel_'.strtolower($acao);
                if (DB::connection()->getDriverName() === 'sqlite') {
                    DB::unprepared("CREATE TRIGGER {$nome} BEFORE {$acao} ON {$tabela} BEGIN SELECT RAISE(ABORT, 'Observacao publica imutavel'); END");
                } else {
                    DB::unprepared("CREATE TRIGGER {$nome} BEFORE {$acao} ON {$tabela} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Observacao publica imutavel'");
                }
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar observações do catálogo; reversão exige procedimento próprio.');
    }
};
