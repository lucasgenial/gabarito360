<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vinculo_eventos', 'valida_ate')) {
            Schema::table('vinculo_eventos', fn (Blueprint $t) => $t->date('valida_ate')->nullable());
        }
        if (!Schema::hasColumn('password_reset_tokens', 'vinculo_id')) {
            Schema::table('password_reset_tokens', fn (Blueprint $t) => $t->foreignId('vinculo_id')->nullable()
                ->constrained('usuario_vinculos')->restrictOnDelete());
        }
    }

    public function down(): void
    {
        throw new RuntimeException('V1: não remover automaticamente o contexto de convites existentes.');
    }
};
