<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vinculo_convites')) return;
        Schema::create('vinculo_convites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vinculo_id')->constrained('usuario_vinculos')->restrictOnDelete();
            $t->char('token_hash', 64)->unique();
            $t->dateTime('expira_em');
            $t->dateTime('aceito_em')->nullable();
            $t->dateTime('recusado_em')->nullable();
            $t->foreignId('convidado_por')->constrained('usuarios')->restrictOnDelete();
            $t->unsignedBigInteger('convidado_por_vinculo_id');
            $t->foreign(['convidado_por', 'convidado_por_vinculo_id'], 'v1_convite_emissor')
                ->references(['usuario_id', 'id'])->on('usuario_vinculos')->restrictOnDelete()->restrictOnUpdate();
            $t->timestamps();
            $t->index(['vinculo_id', 'expira_em']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('V1: não apagar convites ou histórico automaticamente.');
    }
};
