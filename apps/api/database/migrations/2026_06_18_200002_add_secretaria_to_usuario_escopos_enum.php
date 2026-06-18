<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE usuario_escopos MODIFY escopo_tipo ENUM('secretaria', 'rede', 'nucleo', 'escola', 'turma', 'aluno') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE usuario_escopos MODIFY escopo_tipo ENUM('rede', 'nucleo', 'escola', 'turma', 'aluno') NOT NULL");
    }
};
