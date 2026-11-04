<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Legado permanece sem perfil até conciliação explícita, sem inferir função histórica.
        Schema::table('provas', fn (Blueprint $t) => $t->string('perfil_criacao',40)->nullable());
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar o contexto de autoria das provas.');
    }
};
