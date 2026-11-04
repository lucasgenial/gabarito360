<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::create('usuario_funcoes_excecoes',function(Blueprint $t){$t->id();$t->foreignId('usuario_id')->constrained('usuarios');$t->foreignId('espaco_id')->constrained('espacos');$t->boolean('permitir')->default(false);$t->unsignedInteger('versao')->default(1);$t->string('chamado',80);$t->string('motivo',500);$t->foreignId('definida_por')->constrained('usuarios');$t->timestamps();$t->unique(['usuario_id','espaco_id']);});
  Schema::create('usuario_funcoes_eventos',function(Blueprint $t){$t->id();$t->foreignId('usuario_id')->constrained('usuarios');$t->foreignId('espaco_id')->constrained('espacos');$t->foreignId('ator_id')->constrained('usuarios');$t->unsignedBigInteger('acesso_interno_id');$t->string('perfil_ator',40);$t->boolean('permitir');$t->unsignedInteger('versao');$t->string('chamado',80);$t->string('motivo',500);$t->timestamp('ocorrido_em');});
  if(in_array(DB::getDriverName(),['mysql','mariadb'],true)) foreach(['UPDATE','DELETE'] as $op)DB::unprepared("CREATE TRIGGER pf1_funcoes_".strtolower($op)." BEFORE $op ON usuario_funcoes_eventos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Historico de excecoes e imutavel'");
  elseif(DB::getDriverName()==='sqlite')foreach(['UPDATE','DELETE'] as $op)DB::unprepared("CREATE TRIGGER pf1_funcoes_".strtolower($op)." BEFORE $op ON usuario_funcoes_eventos BEGIN SELECT RAISE(ABORT, 'Historico de excecoes e imutavel'); END");
 }
 public function down():void {throw new RuntimeException('Preservar excecoes e auditoria; reversao exige procedimento proprio.');}
};
