<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('copias_lotes', fn(Blueprint $t)=>$t->enum('estado',[
            'aguardando_autorizacao','aguardando_auditoria','aguardando_execucao','em_processamento',
            'aguardando_recibo','concluido','bloqueado',
        ])->change());
        Schema::table('copias_itens', fn(Blueprint $t)=>$t->enum('estado',['selecionado','preparado','publicado'])->default('selecionado')->change());
        Schema::create('copias_execucoes', function (Blueprint $t) {
            $t->foreignUuid('lote_id')->primary()->constrained('copias_lotes')->restrictOnDelete();
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();
            $t->unsignedBigInteger('vinculo_id');
            $t->foreign(['ator_id','vinculo_id'])->references(['usuario_id','id'])->on('usuario_vinculos')->restrictOnDelete()->restrictOnUpdate();
            $t->char('versao_acesso',64);
            $t->json('contexto_evento');
            $t->enum('estado',['pendente','aguardando_recibo','concluido','bloqueado']);
            $t->unsignedInteger('envios')->default(0);
            $t->unsignedInteger('falhas')->default(0);
            $t->string('erro_tecnico',40)->nullable();
            $t->dateTime('proxima_tentativa_em');
            $t->timestamps();
            $t->index(['estado','proxima_tentativa_em']);
        });
        Schema::create('copias_staging', function (Blueprint $t) {
            $t->foreignId('item_id')->primary()->constrained('copias_itens')->restrictOnDelete();
            $t->longText('conteudo_cifrado')->nullable();
            $t->char('digest',64);
            $t->dateTime('created_at');
        });
    }
    public function down(): void { throw new RuntimeException('Preservar execução e proveniência de cópias.'); }
};
