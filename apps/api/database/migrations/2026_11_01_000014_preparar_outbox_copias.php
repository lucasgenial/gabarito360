<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditoria_eventos', function (Blueprint $t) {
            $t->string('metodo',10)->nullable()->change();
            $t->string('rota',255)->nullable()->change();
            $t->unsignedSmallInteger('status_http')->nullable()->change();
        });
        Schema::table('copias_lotes', fn (Blueprint $t) => $t->unsignedTinyInteger('auditoria_versao')->default(0));
        Schema::create('copias_outbox', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('lote_id')->constrained('copias_lotes')->restrictOnDelete();
            $t->json('evento');
            $t->char('digest',64);
            $t->unsignedInteger('envios')->default(0);
            $t->unsignedInteger('falhas')->default(0);
            $t->dateTime('proxima_tentativa_em');
            $t->dateTime('entregue_em')->nullable();
            $t->string('erro_tecnico',32)->nullable();
            $t->dateTime('created_at');
            $t->index(['entregue_em','proxima_tentativa_em'], 't5_outbox_pendentes');
        });
    }
    public function down(): void { throw new RuntimeException('T5: preservar recibos e eventos de domínio.'); }
};
