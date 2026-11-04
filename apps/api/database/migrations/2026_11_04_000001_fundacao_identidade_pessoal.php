<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pessoas', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->enum('situacao', ['provisoria', 'documentada'])->default('provisoria');
            $t->timestamps();
        });
        Schema::create('pessoa_documentos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pessoa_id')->constrained('pessoas')->restrictOnDelete();
            $t->enum('tipo', ['CPF']);
            $t->enum('pais', ['BR']);
            $t->text('valor_cifrado');
            $t->enum('situacao', ['declarado'])->default('declarado');
            $t->timestamp('registrado_em');
            $t->unique(['id', 'pessoa_id'], 'id1_documento_pessoa');
        });
        Schema::create('pessoa_documento_indices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('documento_id')->constrained('pessoa_documentos')->restrictOnDelete();
            $t->enum('tipo', ['CPF']);
            $t->enum('pais', ['BR']);
            $t->unsignedSmallInteger('versao');
            $t->char('indice', 64);
            $t->unique(['tipo', 'pais', 'versao', 'indice'], 'id1_documento_unico');
            $t->unique(['documento_id', 'versao'], 'id1_documento_versao_unica');
        });
        Schema::create('pessoa_identidade_eventos', function (Blueprint $t) {
            $t->id();
            $t->uuid('referencia')->unique();
            $t->foreignId('pessoa_id')->constrained('pessoas')->restrictOnDelete();
            $t->foreignId('documento_id')->nullable()->constrained('pessoa_documentos')->restrictOnDelete();
            $t->foreignId('ator_usuario_id')->nullable()->constrained('usuarios')->restrictOnDelete();
            $t->enum('acao', ['provisoria_criada', 'documento_declarado']);
            $t->timestamp('ocorrido_em');
            $t->foreign(['documento_id', 'pessoa_id'], 'id1_evento_documento_pessoa')
                ->references(['id', 'pessoa_id'])->on('pessoa_documentos')->restrictOnDelete();
        });
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER id1_indice_validar BEFORE INSERT ON pessoa_documento_indices WHEN NEW.versao != 1 OR length(NEW.indice) != 64 OR NEW.indice GLOB '*[^a-f0-9]*' BEGIN SELECT RAISE(ABORT, 'Indice de identidade invalido'); END");
        } else {
            DB::unprepared("CREATE TRIGGER id1_indice_validar BEFORE INSERT ON pessoa_documento_indices FOR EACH ROW BEGIN IF NEW.versao != 1 OR CHAR_LENGTH(NEW.indice) != 64 OR NEW.indice NOT REGEXP BINARY '^[a-f0-9]{64}$' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Indice de identidade invalido'; END IF; END");
        }
        foreach (['pessoa_documentos' => 'documento', 'pessoa_documento_indices' => 'indice', 'pessoa_identidade_eventos' => 'evento'] as $tabela => $sufixo) {
            foreach (['UPDATE', 'DELETE'] as $op) {
                $nome = 'id1_'.$sufixo.'_'.strtolower($op);
                if (DB::getDriverName() === 'sqlite') {
                    DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON $tabela BEGIN SELECT RAISE(ABORT, 'Registro de identidade imutavel'); END");
                } else {
                    DB::unprepared("CREATE TRIGGER $nome BEFORE $op ON $tabela FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Registro de identidade imutavel'");
                }
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar identidade e evidências; recuperação exige procedimento próprio.');
    }
};
