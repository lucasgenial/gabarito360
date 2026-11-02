<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('pedidos_titulares', function (Blueprint $t) {
            $t->unsignedInteger('ciclo_verificacao')->default(1);
            $t->text('resposta_referencia')->nullable();
        });
        Schema::create('pedido_titular_verificacoes', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignUuid('pedido_id')->constrained('pedidos_titulares')->restrictOnDelete();
            $t->unsignedInteger('ciclo'); $t->string('tipo', 20); $t->string('metodo', 40);
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();
            $t->foreignId('acesso_id')->constrained('usuario_acessos_internos')->restrictOnDelete();
            $t->unsignedInteger('acesso_versao'); $t->text('evidencia_referencia');
            $t->timestamp('registrada_em'); $t->dateTime('valida_ate');
            $t->unique(['pedido_id','ciclo','tipo'], 'pedido_verificacao_ciclo_unico');
        });
    }
    public function down(): void { throw new RuntimeException('L4-B2: preservar verificações e respostas; reversão exige procedimento próprio.'); }
};
