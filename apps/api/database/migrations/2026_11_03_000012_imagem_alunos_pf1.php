<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('alunos', fn(Blueprint $t) => $t->unsignedInteger('imagem_versao')->default(1));
        // Autorização expressa PF1: retrato privado também no espaço pessoal.
        // Permanecem todos os bloqueios de CPF, nascimento, gênero e responsável.
        $regra = "NEW.espaco_tipo = 'escola' OR (NEW.espaco_tipo = 'pessoal' AND NEW.espaco_id IS NOT NULL AND NEW.cpf IS NULL AND NEW.cpf_hash IS NULL AND NEW.data_nascimento IS NULL AND NEW.genero IS NULL AND NEW.nome_responsavel IS NULL)";
        foreach (['INSERT','UPDATE'] as $evento) {
            $nome = 't3_alunos_'.strtolower($evento);
            DB::unprepared("DROP TRIGGER IF EXISTS $nome");
            if (DB::getDriverName()==='sqlite') DB::unprepared("CREATE TRIGGER $nome BEFORE $evento ON alunos WHEN NOT ($regra) BEGIN SELECT RAISE(ABORT, 'Dados incompatíveis com tipo de espaço'); END");
            else DB::unprepared("CREATE TRIGGER $nome BEFORE $evento ON alunos FOR EACH ROW BEGIN IF NOT ($regra) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dados incompativeis com tipo de espaco'; END IF; END");
        }
    }
    public function down(): void { throw new RuntimeException('PF1: preservar retratos e histórico; recuperação pelo backup validado.'); }
};
