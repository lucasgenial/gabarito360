<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE usuarios MODIFY perfil ENUM('secretario_educacao', 'admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aplicador', 'aluno') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE usuarios MODIFY perfil ENUM('admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aluno') NOT NULL");
    }
};
