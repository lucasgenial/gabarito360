<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gabarito_questoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gabarito_id')->constrained('gabaritos')->cascadeOnDelete();
            $table->tinyInteger('numero_questao')->unsigned();
            $table->char('alternativa', 1);
            $table->decimal('peso', 5, 2)->default(1.00);
            $table->boolean('anulada')->default(false);

            $table->unique(['gabarito_id', 'numero_questao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gabarito_questoes');
    }
};
