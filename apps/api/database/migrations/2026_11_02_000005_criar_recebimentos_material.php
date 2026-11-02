<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('copias_recebimentos',function(Blueprint $t) {
            $t->uuid('id')->primary(); $t->char('codigo_hash',64)->unique();
            $t->foreignId('destino_espaco_id')->constrained('espacos')->restrictOnDelete();
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete(); $t->unsignedBigInteger('vinculo_id');
            $t->foreign(['ator_id','vinculo_id'])->references(['usuario_id','id'])->on('usuario_vinculos')->restrictOnDelete()->restrictOnUpdate();
            $t->char('versao_acesso',64); $t->dateTime('valida_ate');
            $t->foreignUuid('lote_id')->nullable()->unique()->constrained('copias_lotes')->restrictOnDelete();
            $t->char('conteudo_confirmado_digest',64)->nullable();
            $t->dateTime('created_at'); $t->index(['vinculo_id','valida_ate']);
        });
    }
    public function down(): void { throw new RuntimeException('Preservar indicações vinculadas aos lotes.'); }
};
