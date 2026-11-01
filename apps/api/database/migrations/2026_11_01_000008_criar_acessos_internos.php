<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usuario_acessos_internos')) return;
        Schema::create('usuario_acessos_internos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $t->string('perfil', 24);
            $t->string('situacao', 24);
            $t->timestamp('inicio_em');
            $t->timestamp('fim_em')->nullable();
            $t->unsignedInteger('versao_autorizacao')->default(1);
            $t->foreignId('criado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['usuario_id', 'perfil']);
            $t->index(['usuario_id', 'situacao', 'id']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Acessos internos: reversão exige preservação das atribuições e revogação coordenada.');
    }
};
