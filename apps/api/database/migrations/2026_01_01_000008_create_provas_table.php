<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escola_id')->constrained('escolas')->cascadeOnDelete();
            $table->foreignId('criado_por')->constrained('usuarios')->restrictOnDelete();
            $table->string('titulo', 255);
            $table->string('disciplina', 100);
            $table->string('serie', 50);
            $table->tinyInteger('num_questoes')->unsigned();
            $table->tinyInteger('num_alternativas')->unsigned();
            $table->decimal('nota_maxima', 5, 2)->default(10.00);
            $table->enum('tipo_pontuacao', ['igual', 'personalizado'])->default('igual');
            $table->boolean('anular_se_todas')->default(false);
            $table->boolean('gerar_cartao_pdf')->default(true);
            $table->enum('status', ['rascunho', 'publicada', 'em_correcao', 'corrigida'])->default('rascunho');
            $table->date('data_aplicacao')->nullable();
            $table->timestamps();

            $table->index('escola_id');
            $table->index('criado_por');
            $table->index('status');
            $table->index('disciplina');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provas');
    }
};
