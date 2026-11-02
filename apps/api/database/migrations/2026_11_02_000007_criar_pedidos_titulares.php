<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('espacos', fn (Blueprint $t) => $t->unique(['id','cliente_id'], 'espacos_id_cliente_l4_unique'));
        Schema::create('pedidos_titulares', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->unsignedBigInteger('cliente_id'); $t->unsignedBigInteger('espaco_id');
            $t->foreign(['espaco_id','cliente_id'], 'pedidos_escopo_fk')->references(['id','cliente_id'])->on('espacos')->restrictOnDelete();
            $t->foreignId('responsavel_acesso_id')->constrained('usuario_acessos_internos')->restrictOnDelete();
            $t->unsignedInteger('responsavel_versao'); $t->timestamp('atribuicao_fim_em')->nullable();
            $t->foreignId('criado_por')->constrained('usuarios')->restrictOnDelete();
            $t->string('tipo', 30); $t->string('situacao', 30); $t->unsignedInteger('versao')->default(1);
            $t->text('identificacao_declarada'); $t->text('relato');
            $t->string('resultado', 40)->nullable(); $t->timestamp('concluido_em')->nullable(); $t->timestamps();
            $t->index(['responsavel_acesso_id','responsavel_versao','created_at'], 'pedidos_responsavel_idx');
        });
        Schema::create('pedido_titular_eventos', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->foreignUuid('pedido_id')->constrained('pedidos_titulares')->restrictOnDelete();
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();
            $t->foreignId('acesso_id')->constrained('usuario_acessos_internos')->restrictOnDelete();
            $t->string('acao', 30); $t->string('anterior', 30)->nullable(); $t->string('atual', 30);
            $t->text('motivo'); $t->timestamp('ocorrido_em');
        });
        Schema::create('pedido_titular_recibos', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->foreignId('acesso_id')->constrained('usuario_acessos_internos')->restrictOnDelete();
            $t->unsignedInteger('acesso_versao'); $t->string('acao', 60); $t->uuid('chave');
            $t->char('payload_hash', 64); $t->foreignUuid('pedido_id')->constrained('pedidos_titulares')->restrictOnDelete();
            $t->text('resposta'); $t->timestamp('created_at');
            $t->unique(['acesso_id','acesso_versao','acao','chave'], 'pedido_recibo_unico');
        });
    }
    public function down(): void { throw new RuntimeException('L4: reversão exige preservação dos protocolos e procedimento próprio.'); }
};
