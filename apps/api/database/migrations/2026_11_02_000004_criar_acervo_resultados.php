<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acervo_resultados',function(Blueprint $t) {
            $t->uuid('id')->primary(); $t->uuid('lote_id')->unique();
            $t->unsignedBigInteger('origem_espaco_id'); $t->unsignedBigInteger('destino_espaco_id');
            $t->foreign(['lote_id','origem_espaco_id','destino_espaco_id'],'t5_acervo_lote')
                ->references(['id','origem_espaco_id','destino_espaco_id'])->on('copias_lotes')->restrictOnDelete()->restrictOnUpdate();
            $t->longText('conteudo_cifrado'); $t->char('conteudo_digest',64);
            $t->foreignId('copiado_por')->constrained('usuarios')->restrictOnDelete();
            $t->dateTime('created_at');
        });
        Schema::create('acervo_resultados_itens',function(Blueprint $t) {
            $t->id(); $t->foreignUuid('acervo_id')->constrained('acervo_resultados')->restrictOnDelete();
            $t->foreignId('item_id')->unique()->constrained('copias_itens')->restrictOnDelete();
        });
    }
    public function down(): void { throw new RuntimeException('Preservar o acervo histórico de resultados.'); }
};
