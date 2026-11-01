<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DDL retomável: clientes internos conciliados permanecem intactos.
        foreach (['contato_nome', 'contato_email', 'contato_telefone', 'contrato_referencia'] as $campo) {
            if (!Schema::hasColumn('clientes', $campo)) {
                Schema::table('clientes', fn (Blueprint $t) => $t->text($campo)->nullable());
            }
        }
        if (!Schema::hasTable('cliente_eventos')) {
            Schema::create('cliente_eventos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
                $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();
                $t->string('acao', 32);
                $t->string('modalidade_anterior', 24)->nullable();
                $t->string('modalidade_nova', 24);
                $t->timestamp('ocorrido_em');
                $t->index(['cliente_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Cadastro de clientes: reversão exige plano de preservação dos contatos e eventos.');
    }
};
