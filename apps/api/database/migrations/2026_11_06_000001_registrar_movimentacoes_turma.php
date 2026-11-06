<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
    public function up(): void {
        Schema::create('aluno_movimentacoes',function(Blueprint $t){
            $t->uuid('id')->primary();
            foreach(['aluno_id','espaco_id','escola_id','turma_origem_id','turma_destino_id','ator_id'] as $c) $t->unsignedBigInteger($c);
            $t->unsignedBigInteger('ator_vinculo_id')->nullable();
            $t->string('ator_perfil',40);
            $t->uuid('chave')->unique(); $t->char('pedido_hash',64);
            $t->text('motivo'); $t->dateTime('ocorrido_em',6);
            $t->index(['espaco_id','aluno_id','ocorrido_em']);
        });
        if(in_array(DB::getDriverName(),['mysql','mariadb'],true)) foreach(['UPDATE','DELETE']as$op) DB::unprepared("CREATE TRIGGER aluno_mov_".strtolower($op)." BEFORE $op ON aluno_movimentacoes FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Historico imutavel'");
        elseif(DB::getDriverName()==='sqlite') foreach(['UPDATE','DELETE']as$op) DB::unprepared("CREATE TRIGGER aluno_mov_".strtolower($op)." BEFORE $op ON aluno_movimentacoes BEGIN SELECT RAISE(ABORT, 'Historico imutavel'); END");
    }
    public function down(): void { throw new RuntimeException('Preservar histórico de movimentações; reversão exige procedimento próprio.'); }
};
