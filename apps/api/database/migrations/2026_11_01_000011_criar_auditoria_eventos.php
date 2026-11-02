<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auditoria_eventos', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->char('mes', 7);
            $t->dateTime('ocorrido_em', 6);
            // Sem cascatas: exclusão futura de entidades não apaga o histórico.
            foreach (['usuario_real_id','usuario_visto_id','vinculo_id','acesso_interno_id','espaco_id','cliente_id','escola_id','nucleo_id'] as $campo) $t->unsignedBigInteger($campo)->nullable();
            $t->string('perfil', 40)->nullable();
            $t->string('metodo', 10);
            $t->string('rota', 255);
            $t->json('referencias');
            $t->unsignedSmallInteger('status_http');
            $t->string('resultado', 12);
            $t->string('ip_rede', 50)->nullable();
            $t->index(['mes','ocorrido_em']);
            $t->index(['usuario_real_id','ocorrido_em']);
            $t->index(['espaco_id','ocorrido_em']);
        });
    }

    public function down(): void { Schema::dropIfExists('auditoria_eventos'); }
};
