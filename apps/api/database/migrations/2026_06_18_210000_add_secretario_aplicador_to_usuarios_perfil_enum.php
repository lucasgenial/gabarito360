<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->perfis(['secretario_educacao', 'admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aplicador', 'aluno']);
    }

    public function down(): void
    {
        $this->perfis(['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aluno']);
    }

    /** MySQL/MariaDB: ALTER ... MODIFY; outros bancos (SQLite dos testes): alteração de coluna do Laravel. */
    private function perfis(array $valores): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE usuarios MODIFY perfil ENUM('" . implode("', '", $valores) . "') NOT NULL");
            return;
        }
        Schema::table('usuarios', fn (Blueprint $t) => $t->enum('perfil', $valores)->change());
    }
};
