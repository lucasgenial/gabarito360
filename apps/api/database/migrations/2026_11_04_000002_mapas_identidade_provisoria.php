<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        foreach (['pessoa_usuarios' => ['usuario_id', 'usuarios'], 'pessoa_alunos' => ['aluno_id', 'alunos']] as $tabela => [$coluna, $origem]) {
            Schema::create($tabela, function (Blueprint $t) use ($coluna, $origem) {
                $t->id();
                $t->foreignId('pessoa_id')->constrained('pessoas')->restrictOnDelete();
                $t->foreignId($coluna)->unique()->constrained($origem)->restrictOnDelete();
                $t->uuid('execucao_referencia')->index();
                $t->timestamp('registrado_em');
                if ($origem === 'usuarios') $t->unique('pessoa_id');
            });
            foreach (['UPDATE', 'DELETE'] as $op) {
                $nome = 'id1_mapa_'.($origem === 'usuarios' ? 'usuario' : 'aluno').'_'.strtolower($op);
                if (DB::getDriverName() === 'sqlite') {
                    DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON $tabela BEGIN SELECT RAISE(ABORT, 'Mapa historico de identidade imutavel'); END");
                } else {
                    DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON $tabela FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Mapa historico de identidade imutavel'");
                }
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar mapas históricos; recuperação exige procedimento próprio.');
    }
};
