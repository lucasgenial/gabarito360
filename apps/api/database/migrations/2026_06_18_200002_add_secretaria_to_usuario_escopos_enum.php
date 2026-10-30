<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->tipos(['secretaria', 'rede', 'nucleo', 'escola', 'turma', 'aluno']);
    }

    public function down(): void
    {
        $this->tipos(['rede', 'nucleo', 'escola', 'turma', 'aluno']);
    }

    /** MySQL/MariaDB: ALTER ... MODIFY; outros bancos (SQLite dos testes): alteração de coluna do Laravel. */
    private function tipos(array $valores): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE usuario_escopos MODIFY escopo_tipo ENUM('" . implode("', '", $valores) . "') NOT NULL");
            return;
        }
        Schema::table('usuario_escopos', fn (Blueprint $t) => $t->enum('escopo_tipo', $valores)->change());
    }
};
