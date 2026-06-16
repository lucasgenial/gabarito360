<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gabaritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prova_id')->unique()->constrained('provas')->cascadeOnDelete();
            $table->foreignId('publicado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('publicado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gabaritos');
    }
};
