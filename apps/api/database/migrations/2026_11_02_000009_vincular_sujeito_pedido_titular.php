<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('pedidos_titulares', function (Blueprint $t) {
            $t->foreignId('aluno_id')->nullable()->constrained('alunos')->restrictOnDelete();
            $t->unsignedInteger('sujeito_versao')->default(0);
            $t->text('sujeito_evidencia')->nullable();
        });
        Schema::create('pedido_titular_sujeitos', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignUuid('pedido_id')->constrained('pedidos_titulares')->restrictOnDelete();
            $t->unsignedInteger('versao');
            $t->foreignId('aluno_id')->constrained('alunos')->restrictOnDelete();
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();
            $t->text('evidencia');
            $t->dateTime('registrado_em');
            $t->unique(['pedido_id','versao'], 'pedido_sujeito_versao_unica');
        });
    }
    public function down(): void { throw new RuntimeException('L4-C2: preservar vínculos e histórico; reversão exige procedimento próprio.'); }
};
