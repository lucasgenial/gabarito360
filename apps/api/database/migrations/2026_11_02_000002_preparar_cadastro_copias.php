<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('copias_lotes',fn(Blueprint $t)=>$t->enum('finalidade',['preparacao_material','cadastro_minimo'])->change());
        Schema::table('registros_origem',fn(Blueprint $t)=>$t->dropForeign(DB::connection()->getDriverName()==='sqlite'
            ? ['lote_id','entidade','origem_id','origem_espaco_id','destino_espaco_id'] : 't5_mapa_item'));
        foreach (['copias_itens','registros_origem'] as $tabela) Schema::table($tabela,fn(Blueprint $t)=>$t->enum('entidade',['prova','turma','aluno'])->change());
        Schema::table('registros_origem',function(Blueprint $t) {
            $t->foreign(['lote_id','entidade','origem_id','origem_espaco_id','destino_espaco_id'],'t5_mapa_item')
                ->references(['lote_id','entidade','origem_id','origem_espaco_id','destino_espaco_id'])->on('copias_itens')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::create('copias_cadastros',function(Blueprint $t) {
            $t->foreignUuid('lote_id')->primary()->constrained('copias_lotes')->restrictOnDelete();
            $t->char('conteudo_digest',64);
            $t->char('conciliacao_digest',64)->nullable();
        });
        Schema::create('copias_evidencias',function(Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('lote_id')->unique()->constrained('copias_lotes')->restrictOnDelete();
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();
            $t->unsignedBigInteger('vinculo_id');
            $t->foreign(['ator_id','vinculo_id'])->references(['usuario_id','id'])->on('usuario_vinculos')->restrictOnDelete()->restrictOnUpdate();
            $t->char('versao_acesso',64);
            $t->char('selecao_digest',64); $t->char('conteudo_digest',64);
            $t->json('categorias');
            $t->longText('documento_cifrado'); $t->char('documento_digest',64);
            $t->enum('confirmacao',['aplicabilidade_revisada']);
            $t->dateTime('valida_ate'); $t->dateTime('revogada_em')->nullable(); $t->dateTime('created_at');
        });
    }
    public function down(): void { throw new RuntimeException('Preservar evidências e proveniência do cadastro.'); }
};
