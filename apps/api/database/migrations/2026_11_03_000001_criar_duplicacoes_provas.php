<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('provas', fn (Blueprint $t) => $t->string('formato_avaliacao',40)->default('gabarito'));
        DB::table('permissoes')->insertOrIgnore(['chave'=>'prova.duplicar_propria','modulo'=>'provas','descricao'=>'Copiar a própria prova como rascunho independente']);
        foreach (['professor','professor_autonomo'] as $perfil) DB::table('perfil_permissoes')->insertOrIgnore(['perfil'=>$perfil,'permissao'=>'prova.duplicar_propria','tipo'=>'padrao']);
        Schema::create('prova_duplicacoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('prova_id')->unique()->constrained('provas')->restrictOnDelete();
            $t->foreignId('origem_id')->constrained('provas')->restrictOnDelete();
            $t->foreignId('raiz_id')->constrained('provas')->restrictOnDelete();
            $t->foreignId('autor_original_id')->constrained('usuarios')->restrictOnDelete();
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();
            $t->foreignId('vinculo_id')->constrained('usuario_vinculos')->restrictOnDelete();
            $t->foreignId('espaco_id')->constrained('espacos')->restrictOnDelete();
            $t->uuid('chave'); $t->char('payload_hash',64);
            $t->unsignedSmallInteger('contrato_versao')->default(1);
            $t->dateTime('created_at');
            $t->unique(['vinculo_id','chave']);
        });
    }
    public function down(): void { throw new RuntimeException('Preservar procedência de provas; reversão exige procedimento próprio.'); }
};
