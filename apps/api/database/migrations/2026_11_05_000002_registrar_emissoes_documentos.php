<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_emissoes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->char('codigo_hash', 64)->unique();
            $t->char('arquivo_sha256', 64);
            $t->dateTime('emitido_em');
            $t->string('modelo', 20);
            $t->unsignedInteger('quantidade');
            $t->string('instituicao', 255);
            $t->unsignedBigInteger('emissor_id')->nullable();
            $t->unsignedBigInteger('vinculo_id')->nullable();
            $t->unsignedBigInteger('espaco_id')->nullable();
            $t->string('perfil', 50);
            $t->char('selo', 64);
        });
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        foreach (['UPDATE', 'DELETE'] as $acao) {
            $nome = 'documento_emissoes_'.strtolower($acao);
            DB::unprepared($sqlite ? "CREATE TRIGGER $nome BEFORE $acao ON documento_emissoes BEGIN SELECT RAISE(ABORT,'Emissao imutavel'); END"
                : "CREATE TRIGGER $nome BEFORE $acao ON documento_emissoes FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Emissao imutavel'");
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar registros de emissão de documentos.');
    }
};
