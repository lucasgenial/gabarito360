<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nucleo_id')->constrained('nucleos')->cascadeOnDelete();
            $table->foreignId('escola_id')->constrained('escolas')->cascadeOnDelete();
            $table->foreignId('agendado_por')->constrained('usuarios')->restrictOnDelete();
            $table->date('data_visita');
            $table->string('tipo', 100);
            $table->enum('urgencia', ['prioritaria', 'monitorar', 'referencia']);
            $table->timestamps();

            $table->index('nucleo_id');
            $table->index('escola_id');
            $table->index('data_visita');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};
