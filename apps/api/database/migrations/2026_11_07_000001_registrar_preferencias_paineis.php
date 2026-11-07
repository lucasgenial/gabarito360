<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('preferencias_paineis', function (Blueprint $t) {
            $t->id();
            foreach (['usuario_id','vinculo_id','espaco_id'] as $c) $t->unsignedBigInteger($c);
            $t->string('perfil', 50); $t->string('painel', 64); $t->char('contexto_hash', 64);
            $t->string('recurso_tipo', 16); $t->unsignedBigInteger('recurso_id')->nullable();
            $t->unsignedInteger('versao')->default(0); $t->string('apresentacao', 10)->default('tabela');
            $t->longText('desktop'); $t->longText('mobile'); $t->dateTime('atualizado_em', 6);
            $t->unique(['usuario_id','contexto_hash','painel'], 'preferencias_paineis_contexto');
        });
        Schema::create('preferencias_painel_eventos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('preferencia_id'); $t->uuid('chave');
            $t->char('pedido_hash', 64); $t->string('acao', 10); $t->unsignedInteger('versao');
            $t->longText('resultado'); $t->dateTime('ocorrido_em', 6);
            $t->unique(['preferencia_id','chave'], 'preferencia_painel_idempotencia');
            $t->unique(['preferencia_id','versao'], 'preferencia_painel_sequencia');
        });
        foreach (['UPDATE','DELETE'] as $op) {
            $nome = 'preferencia_painel_'.strtolower($op);
            if (DB::getDriverName() === 'sqlite') DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON preferencias_painel_eventos BEGIN SELECT RAISE(ABORT,'Evento de preferencia imutavel'); END");
            else DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON preferencias_painel_eventos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Evento de preferencia imutavel'");
        }
    }
    public function down(): void { throw new RuntimeException('Preservar preferencias e seus eventos.'); }
};
