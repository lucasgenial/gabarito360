<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};

return new class extends Migration {
    public function up():void
    {
        Schema::create('vinculos_docentes_compostos',function(Blueprint $t){
            $t->unsignedBigInteger('id')->autoIncrement();
            foreach(['espaco_id','ator_vinculo_id','turma_id','disciplina_id','professor_vinculo_id','atribuicao_id'] as $c)$t->unsignedBigInteger($c);
            $t->uuid('chave');$t->char('pedido_hash',64);$t->longText('resultado');$t->dateTime('ocorrido_em',6);
            $t->unique(['ator_vinculo_id','chave'],'docente_composto_idempotencia');
            $t->index(['espaco_id','turma_id']);
        });
        foreach(['UPDATE','DELETE']as$op){
            $nome='docente_composto_'.strtolower($op);
            if(DB::getDriverName()==='sqlite')DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON vinculos_docentes_compostos BEGIN SELECT RAISE(ABORT,'Recibo docente imutavel'); END");
            else DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON vinculos_docentes_compostos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Recibo docente imutavel'");
        }
    }
    public function down():void {throw new RuntimeException('Preservar recibos e histórico docente.');}
};
