<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasIndex('usuario_vinculos', 'v1_vinculo_titular')) {
            Schema::table('usuario_vinculos', fn (Blueprint $t) => $t->unique(['usuario_id', 'id'], 'v1_vinculo_titular'));
        }
        if (!Schema::hasColumn('cartao_envios', 'vinculo_id')) {
            Schema::table('cartao_envios', fn (Blueprint $t) => $t->unsignedBigInteger('vinculo_id')->nullable());
        }
        if (!Schema::hasColumn('cartao_envios', 'chave_cliente')) {
            Schema::table('cartao_envios', fn (Blueprint $t) => $t->uuid('chave_cliente')->nullable());
        }
        if (!collect(Schema::getForeignKeys('cartao_envios'))->contains('name', 'v1_envio_titular')) {
            Schema::table('cartao_envios', fn (Blueprint $t) => $t->foreign(['usuario_id', 'vinculo_id'], 'v1_envio_titular')
                ->references(['usuario_id', 'id'])->on('usuario_vinculos')->restrictOnDelete()->restrictOnUpdate());
        }
    }

    public function down(): void
    {
        throw new RuntimeException('V1: não remover contexto de recibos automaticamente.');
    }
};
