<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalogo_observacoes', fn(Blueprint $t) => $t->unique(['id','importacao_id'], 'cat_obs_id_import_unique'));
        Schema::create('catalogo_ufs', function (Blueprint $t) {
            $t->char('geografia_id',64); $t->unsignedTinyInteger('codigo_ibge'); $t->char('sigla',2); $t->string('nome',100);
            $t->primary(['geografia_id','codigo_ibge']); $t->unique(['geografia_id','sigla'],'cat_ufs_sigla_unique');
            $t->foreign('geografia_id')->references('id')->on('catalogo_geografias')->restrictOnDelete();
        });
        Schema::create('catalogo_municipios', function (Blueprint $t) {
            $t->char('geografia_id',64); $t->char('codigo_ibge',7); $t->char('uf',2);
            $t->string('nome',150); $t->string('nome_normalizado',200);
            $t->primary(['geografia_id','codigo_ibge']);
            $t->unique(['geografia_id','codigo_ibge','uf'],'cat_municipio_uf_unique');
            $t->foreign(['geografia_id','uf'],'cat_municipio_uf_fk')->references(['geografia_id','sigla'])->on('catalogo_ufs')->restrictOnDelete();
            $t->index(['geografia_id','uf','nome_normalizado'],'cat_municipio_busca_idx');
        });
        Schema::create('catalogo_instituicoes', function (Blueprint $t) { $t->uuid('id')->primary(); $t->dateTime('created_at'); });
        Schema::create('catalogo_identificadores', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->string('namespace',30); $t->string('codigo',30);
            $t->foreignUuid('instituicao_id')->constrained('catalogo_instituicoes')->restrictOnDelete();
            $t->unique(['namespace','codigo'],'cat_identificador_unique');
            $t->unique(['instituicao_id','namespace','codigo'],'cat_identificador_destino_unique');
        });
        Schema::create('catalogo_unidades', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->foreignUuid('instituicao_id')->constrained('catalogo_instituicoes')->restrictOnDelete();
            $t->string('chave_unidade',30); $t->string('natureza',30);
            $t->unique(['instituicao_id','chave_unidade'],'cat_unidade_chave_unique'); $t->unique(['id','instituicao_id'],'cat_unidade_instituicao_unique');
        });
        Schema::create('catalogo_publicacoes', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->foreignUuid('importacao_id')->unique()->constrained('catalogo_importacoes')->restrictOnDelete();
            $t->char('geografia_id',64); $t->foreign('geografia_id')->references('id')->on('catalogo_geografias')->restrictOnDelete();
            $t->string('fonte',20); $t->unsignedSmallInteger('ano'); $t->string('estado',20);
            $t->unsignedInteger('total'); $t->unsignedInteger('cursor')->default(0);
            $t->uuid('anterior_id')->nullable(); $t->foreign('anterior_id')->references('id')->on('catalogo_publicacoes')->restrictOnDelete();
            $t->string('motivo',500)->nullable(); $t->uuid('lease_token')->nullable(); $t->dateTime('lease_ate')->nullable();
            $t->char('projecao_sha256',64)->nullable(); $t->dateTime('publicado_em')->nullable(); $t->timestamps();
            $t->unique(['id','importacao_id'],'cat_publicacao_import_unique'); $t->unique(['id','geografia_id'],'cat_publicacao_geo_unique');
            $t->unique(['id','fonte'],'cat_publicacao_fonte_unique');
        });
        Schema::create('catalogo_fontes', function (Blueprint $t) {
            $t->string('nome',20)->primary(); $t->foreignUuid('publicacao_id')->nullable()->constrained('catalogo_publicacoes')->restrictOnDelete();
            $t->foreign(['publicacao_id','nome'],'cat_fonte_publicacao_fk')->references(['id','fonte'])->on('catalogo_publicacoes')->restrictOnDelete();
        });
        DB::table('catalogo_fontes')->insert([['nome'=>'basica'],['nome'=>'superior']]);
        Schema::create('catalogo_entradas', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->uuid('publicacao_id'); $t->uuid('importacao_id'); $t->char('geografia_id',64);
            $t->uuid('instituicao_id'); $t->uuid('unidade_id'); $t->unsignedBigInteger('observacao_id'); $t->unsignedInteger('ordem');
            $t->string('namespace',30); $t->string('codigo',30); $t->string('natureza',30);
            $t->string('nome',200); $t->string('sigla',40)->nullable(); $t->string('nome_normalizado',250); $t->string('sigla_normalizada',80)->nullable();
            $t->string('tipo',30); $t->string('dependencia_fonte',10); $t->string('dependencia',20)->nullable();
            $t->string('situacao_fonte',10)->nullable(); $t->char('uf',2); $t->char('municipio_ibge',7); $t->char('projecao_sha256',64);
            $t->unique(['publicacao_id','unidade_id'],'cat_entrada_unidade_unique'); $t->unique(['publicacao_id','ordem'],'cat_entrada_ordem_unique');
            $t->foreign(['publicacao_id','importacao_id'],'cat_entrada_publicacao_fk')->references(['id','importacao_id'])->on('catalogo_publicacoes')->restrictOnDelete();
            $t->foreign(['publicacao_id','geografia_id'],'cat_entrada_geo_pub_fk')->references(['id','geografia_id'])->on('catalogo_publicacoes')->restrictOnDelete();
            $t->foreign(['observacao_id','importacao_id'],'cat_entrada_observacao_fk')->references(['id','importacao_id'])->on('catalogo_observacoes')->restrictOnDelete();
            $t->foreign(['unidade_id','instituicao_id'],'cat_entrada_unidade_fk')->references(['id','instituicao_id'])->on('catalogo_unidades')->restrictOnDelete();
            $t->foreign(['instituicao_id','namespace','codigo'],'cat_entrada_identificador_fk')->references(['instituicao_id','namespace','codigo'])->on('catalogo_identificadores')->restrictOnDelete();
            $t->foreign(['geografia_id','municipio_ibge','uf'],'cat_entrada_municipio_fk')->references(['geografia_id','codigo_ibge','uf'])->on('catalogo_municipios')->restrictOnDelete();
            $t->index(['publicacao_id','uf','municipio_ibge','tipo'],'cat_entrada_local_idx');
            $t->index(['publicacao_id','uf','nome_normalizado','unidade_id'],'cat_entrada_nome_idx');
            $t->index(['publicacao_id','namespace','codigo'],'cat_entrada_codigo_idx');
        });
        Schema::create('catalogo_publicacao_eventos', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->foreignUuid('publicacao_id')->constrained('catalogo_publicacoes')->restrictOnDelete();
            $t->uuid('anterior_id')->nullable(); $t->foreign('anterior_id')->references('id')->on('catalogo_publicacoes')->restrictOnDelete();
            $t->string('acao',30); $t->string('motivo',500)->nullable(); $t->char('projecao_sha256',64)->nullable(); $t->dateTime('ocorrido_em');
        });
        foreach (['catalogo_ufs','catalogo_municipios','catalogo_instituicoes','catalogo_identificadores','catalogo_unidades','catalogo_entradas','catalogo_publicacao_eventos'] as $tabela) {
            foreach (['UPDATE','DELETE'] as $acao) {
                $nome = $tabela.'_imutavel_'.strtolower($acao);
                DB::unprepared(DB::connection()->getDriverName() === 'sqlite'
                    ? "CREATE TRIGGER {$nome} BEFORE {$acao} ON {$tabela} BEGIN SELECT RAISE(ABORT, 'Projecao publica imutavel'); END"
                    : "CREATE TRIGGER {$nome} BEFORE {$acao} ON {$tabela} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Projecao publica imutavel'");
            }
        }
        foreach (['UPDATE','DELETE'] as $acao) {
            $nome = 'catalogo_publicacoes_finalizadas_'.strtolower($acao);
            DB::unprepared(DB::connection()->getDriverName() === 'sqlite'
                ? "CREATE TRIGGER {$nome} BEFORE {$acao} ON catalogo_publicacoes WHEN OLD.estado = 'publicada' BEGIN SELECT RAISE(ABORT, 'Projecao publica imutavel'); END"
                : "CREATE TRIGGER {$nome} BEFORE {$acao} ON catalogo_publicacoes FOR EACH ROW BEGIN IF OLD.estado = 'publicada' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Projecao publica imutavel'; END IF; END");
        }
    }

    public function down(): void { throw new RuntimeException('Preservar publicações e identidades; reversão exige procedimento próprio.'); }
};
