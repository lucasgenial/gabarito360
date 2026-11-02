<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('copias_lotes',fn(Blueprint $t)=>$t->enum('finalidade',['preparacao_material','cadastro_minimo','resultados_identificados'])->change());
        Schema::table('registros_origem',fn(Blueprint $t)=>$t->dropForeign(DB::connection()->getDriverName()==='sqlite'
            ? ['lote_id','entidade','origem_id','origem_espaco_id','destino_espaco_id'] : 't5_mapa_item'));
        foreach (['copias_itens','registros_origem'] as $tabela) Schema::table($tabela,fn(Blueprint $t)=>$t->enum('entidade',['prova','turma','aluno','nota'])->change());
        Schema::table('registros_origem',function(Blueprint $t) {
            $t->foreign(['lote_id','entidade','origem_id','origem_espaco_id','destino_espaco_id'],'t5_mapa_item')
                ->references(['lote_id','entidade','origem_id','origem_espaco_id','destino_espaco_id'])->on('copias_itens')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::create('copias_resultados',function(Blueprint $t) {
            $t->foreignUuid('lote_id')->primary()->constrained('copias_lotes')->restrictOnDelete();
            $t->longText('conteudo_cifrado'); $t->char('conteudo_digest',64);
            $t->char('solicitante_versao',64);
            $t->dateTime('capturado_em');
        });
        Schema::create('copias_resultados_decisoes',function(Blueprint $t) {
            $t->foreignUuid('decisao_id')->primary()->constrained('copias_autorizacoes')->restrictOnDelete();
            $t->char('conteudo_digest',64);
            $t->boolean('legado_reconhecido');
        });
    }
    public function down(): void { throw new RuntimeException('Preservar pacotes históricos e evidências de resultados.'); }
};
