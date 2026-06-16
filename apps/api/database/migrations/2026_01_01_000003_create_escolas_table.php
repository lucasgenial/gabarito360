<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escolas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nucleo_id')->constrained('nucleos')->cascadeOnDelete();
            $table->string('nome', 200);
            $table->char('inep', 8)->unique();
            $table->enum('tipo_rede', ['estadual', 'municipal', 'federal', 'privada']);
            $table->string('logradouro', 255)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->char('uf', 2)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('nucleo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escolas');
    }
};
