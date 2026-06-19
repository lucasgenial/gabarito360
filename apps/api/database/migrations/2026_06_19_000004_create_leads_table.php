<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plano_id')->nullable()->constrained('planos')->nullOnDelete();
            $table->string('nome', 200);
            $table->string('email', 150);
            $table->string('telefone', 20)->nullable();
            $table->string('instituicao', 200)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->unsignedInteger('licencas_estimadas')->nullable();
            $table->text('mensagem')->nullable();
            $table->enum('status', ['novo', 'em_contato', 'convertido', 'descartado'])->default('novo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
