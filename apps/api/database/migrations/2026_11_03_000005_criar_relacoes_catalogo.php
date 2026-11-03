<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalogo_entradas',fn(Blueprint $t)=>$t->unique(['id','publicacao_id'],'cat_entrada_id_pub_unique'));
        Schema::create('catalogo_relacao_conjuntos',function(Blueprint $t) {
            $t->uuid('id')->primary(); $t->char('chave',64)->unique();
            $t->foreignUuid('basica_id')->constrained('catalogo_publicacoes')->restrictOnDelete();
            $t->foreignUuid('superior_id')->constrained('catalogo_publicacoes')->restrictOnDelete();
            $t->char('arquivo_sha256',64); $t->string('schema_versao',40); $t->longText('manifesto');
            $t->string('estado',20); $t->unsignedInteger('total'); $t->unsignedInteger('cursor')->default(0);
            $t->unsignedInteger('pendencias')->default(0); $t->char('projecao_sha256',64)->nullable();
            $t->uuid('anterior_id')->nullable(); $t->foreign('anterior_id')->references('id')->on('catalogo_relacao_conjuntos')->restrictOnDelete();
            $t->uuid('lease_token')->nullable(); $t->dateTime('lease_ate')->nullable(); $t->dateTime('publicado_em')->nullable(); $t->timestamps();
            $t->unique(['id','basica_id'],'cat_rel_conjunto_base_unique');
        });
        Schema::create('catalogo_relacao_vigencia',function(Blueprint $t) {
            $t->string('nome',20)->primary(); $t->foreignUuid('conjunto_id')->nullable()->constrained('catalogo_relacao_conjuntos')->restrictOnDelete();
        });
        DB::table('catalogo_relacao_vigencia')->insert(['nome'=>'nacional']);
        Schema::create('catalogo_relacoes',function(Blueprint $t) {
            $t->bigIncrements('id'); $t->uuid('conjunto_id'); $t->unsignedInteger('ordem'); $t->unsignedInteger('linha_fonte');
            $t->char('origem_codigo',8); $t->unsignedBigInteger('origem_entrada_id'); $t->uuid('origem_publicacao_id');
            $t->string('tipo',25); $t->string('alvo_namespace',30); $t->string('alvo_codigo_bruto',14); $t->string('alvo_codigo',14);
            $t->unsignedBigInteger('alvo_entrada_id')->nullable(); $t->foreignUuid('alvo_publicacao_id')->constrained('catalogo_publicacoes')->restrictOnDelete();
            $t->string('estado',20); $t->boolean('uf_diferente')->nullable(); $t->char('projecao_sha256',64);
            $t->unique(['conjunto_id','ordem'],'cat_rel_ordem_unique'); $t->unique(['conjunto_id','origem_codigo','tipo'],'cat_rel_origem_tipo_unique');
            $t->foreign(['conjunto_id','origem_publicacao_id'],'cat_rel_conjunto_base_fk')->references(['id','basica_id'])->on('catalogo_relacao_conjuntos')->restrictOnDelete();
            $t->foreign(['origem_entrada_id','origem_publicacao_id'],'cat_rel_origem_fk')->references(['id','publicacao_id'])->on('catalogo_entradas')->restrictOnDelete();
            $t->foreign(['alvo_entrada_id','alvo_publicacao_id'],'cat_rel_alvo_fk')->references(['id','publicacao_id'])->on('catalogo_entradas')->restrictOnDelete();
        });
        Schema::create('catalogo_relacao_lacunas',function(Blueprint $t) {
            $t->unsignedBigInteger('relacao_id')->primary(); $t->foreign('relacao_id')->references('id')->on('catalogo_relacoes')->restrictOnDelete();
            $t->string('motivo',25); $t->dateTime('registrada_em');
        });
        Schema::create('catalogo_relacao_eventos',function(Blueprint $t) {
            $t->bigIncrements('id'); $t->foreignUuid('conjunto_id')->unique()->constrained('catalogo_relacao_conjuntos')->restrictOnDelete();
            $t->uuid('anterior_id')->nullable(); $t->foreign('anterior_id')->references('id')->on('catalogo_relacao_conjuntos')->restrictOnDelete();
            $t->char('projecao_sha256',64); $t->unsignedInteger('total'); $t->unsignedInteger('pendencias'); $t->dateTime('ocorrido_em');
        });
        foreach (['catalogo_relacoes','catalogo_relacao_lacunas','catalogo_relacao_eventos'] as $tabela) foreach (['UPDATE','DELETE'] as $acao) {
            $nome = $tabela.'_imutavel_'.strtolower($acao);
            DB::unprepared(DB::connection()->getDriverName() === 'sqlite'
                ? "CREATE TRIGGER {$nome} BEFORE {$acao} ON {$tabela} BEGIN SELECT RAISE(ABORT, 'Relacao publica imutavel'); END"
                : "CREATE TRIGGER {$nome} BEFORE {$acao} ON {$tabela} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Relacao publica imutavel'");
        }
        foreach (['UPDATE','DELETE'] as $acao) {
            $nome = 'cat_rel_conjunto_final_'.strtolower($acao);
            DB::unprepared(DB::connection()->getDriverName() === 'sqlite'
                ? "CREATE TRIGGER {$nome} BEFORE {$acao} ON catalogo_relacao_conjuntos WHEN OLD.estado='publicado' BEGIN SELECT RAISE(ABORT, 'Relacao publica imutavel'); END"
                : "CREATE TRIGGER {$nome} BEFORE {$acao} ON catalogo_relacao_conjuntos FOR EACH ROW BEGIN IF OLD.estado='publicado' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Relacao publica imutavel'; END IF; END");
        }
        $guardas = [
            'catalogo_observacoes'=>["SELECT estado FROM catalogo_importacoes WHERE id=NEW.importacao_id",'validando'],
            'catalogo_entradas'=>["SELECT estado FROM catalogo_publicacoes WHERE id=NEW.publicacao_id",'preparando'],
            'catalogo_relacoes'=>["SELECT estado FROM catalogo_relacao_conjuntos WHERE id=NEW.conjunto_id",'preparando'],
            'catalogo_relacao_lacunas'=>["SELECT c.estado FROM catalogo_relacao_conjuntos c JOIN catalogo_relacoes r ON r.conjunto_id=c.id WHERE r.id=NEW.relacao_id",'preparando'],
            'catalogo_relacao_eventos'=>["SELECT estado FROM catalogo_relacao_conjuntos WHERE id=NEW.conjunto_id",'preparando'],
        ];
        foreach ($guardas as $tabela=>[$consulta,$estado]) {
            $nome = $tabela.'_pai_insert';
            DB::unprepared(DB::connection()->getDriverName() === 'sqlite'
                ? "CREATE TRIGGER {$nome} BEFORE INSERT ON {$tabela} WHEN COALESCE(({$consulta}),'') <> '{$estado}' BEGIN SELECT RAISE(ABORT, 'Pai do catalogo selado ou indisponivel'); END"
                : "CREATE TRIGGER {$nome} BEFORE INSERT ON {$tabela} FOR EACH ROW BEGIN IF COALESCE(({$consulta}),'') <> '{$estado}' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pai do catalogo selado ou indisponivel'; END IF; END");
        }
    }
    public function down(): void { throw new RuntimeException('Preservar relações e lacunas; reversão exige procedimento próprio.'); }
};
