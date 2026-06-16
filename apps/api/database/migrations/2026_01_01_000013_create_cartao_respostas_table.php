<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartao_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_id')->constrained('cartoes')->cascadeOnDelete();
            $table->tinyInteger('numero_questao')->unsigned();
            $table->char('alternativa', 1)->nullable();
            $table->decimal('confianca', 5, 4)->nullable();
            $table->boolean('ambigua')->default(false);
            $table->json('alternativas_detectadas')->nullable();

            $table->unique(['cartao_id', 'numero_questao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartao_respostas');
    }
};
