<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::create('cadastro_arquivamentos',function(Blueprint $t){
   $t->uuid('id')->primary();$t->string('entidade',10);$t->unsignedBigInteger('registro_id');
   $t->unsignedBigInteger('espaco_id')->nullable();$t->unsignedBigInteger('usuario_id');$t->unsignedBigInteger('vinculo_id')->nullable();$t->string('ator_perfil',40);
   $t->uuid('chave')->unique();$t->char('pedido_hash',64);$t->boolean('ativo_antes');$t->boolean('ativo_depois');
   $t->unsignedBigInteger('versao');$t->unsignedBigInteger('sequencia')->nullable();$t->boolean('alterado');$t->text('motivo')->nullable();$t->longText('resultado');$t->dateTime('ocorrido_em',6);
   $t->unique(['entidade','registro_id','sequencia'],'cadastro_arquivamento_sequencia');$t->index(['entidade','registro_id','versao'],'cadastro_arquivamento_estado');
  });
  foreach(['UPDATE','DELETE']as$op){$nome='cadastro_arquivo_'.strtolower($op);
   if(DB::getDriverName()==='sqlite')DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON cadastro_arquivamentos BEGIN SELECT RAISE(ABORT,'Arquivamento imutavel'); END");
   else DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON cadastro_arquivamentos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Arquivamento imutavel'");
  }
 }
 public function down():void {throw new RuntimeException('Preservar recibos de arquivamento; reversão exige procedimento próprio.');}
};
