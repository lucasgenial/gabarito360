<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->enum('publico', ['individual', 'institucional']);
            $table->enum('modo', ['autoatendimento', 'comercial']);
            $table->decimal('preco_fixo', 10, 2)->nullable();
            $table->decimal('preco_por_licenca', 10, 2)->nullable();
            $table->unsignedInteger('licencas_min')->nullable();
            $table->unsignedInteger('licencas_max')->nullable();
            $table->enum('periodicidade', ['mensal', 'anual'])->nullable();
            $table->json('limites')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos');
    }
};
