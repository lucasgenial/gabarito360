<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preferencias_usuario', function (Blueprint $table) {
            $table->boolean('tema_sistema')->default(false)->after('reduzir_movimento');
            $table->string('regiao', 40)->nullable()->after('idioma');
            $table->json('acessibilidade')->nullable()->after('regiao');
            $table->json('notificacoes')->nullable()->after('acessibilidade');
        });
    }

    public function down(): void
    {
        Schema::table('preferencias_usuario', function (Blueprint $table) {
            $table->dropColumn(['tema_sistema', 'regiao', 'acessibilidade', 'notificacoes']);
        });
    }
};
