<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->char('cpf', 11)->nullable()->unique()->after('matricula');
            $table->enum('genero', ['M', 'F', 'O'])->nullable()->after('data_nascimento');
            $table->string('foto_path', 255)->nullable()->after('genero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'genero', 'foto_path']);
        });
    }
};
