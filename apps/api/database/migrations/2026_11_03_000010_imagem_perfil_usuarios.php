<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void {
        Schema::table('usuarios',function(Blueprint $t){$t->unsignedTinyInteger('avatar_id')->nullable();$t->string('foto_path')->nullable();$t->unsignedInteger('imagem_versao')->default(1);});
    }
    public function down():void {throw new RuntimeException('Preservar imagens e identidades; reversão requer procedimento próprio.');}
};
