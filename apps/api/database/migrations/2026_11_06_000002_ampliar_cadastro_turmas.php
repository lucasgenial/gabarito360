<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->string('sala', 50)->nullable();
            $table->unsignedSmallInteger('capacidade')->nullable();
            $table->string('etapa_ensino', 40)->nullable();
            $table->text('observacoes')->nullable();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Os dados cadastrais das turmas devem ser preservados. Use correção progressiva.');
    }
};
