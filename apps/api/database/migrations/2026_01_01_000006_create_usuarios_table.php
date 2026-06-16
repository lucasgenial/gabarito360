<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->enum('perfil', ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aluno']);
            $table->string('nome', 200);
            $table->string('email', 150)->unique();
            $table->char('cpf', 11)->unique();
            $table->string('password', 255);
            $table->boolean('ativo')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
