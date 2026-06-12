<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escolas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('nucleo_id');
            $table->string('codigo', 50);
            $table->string('nome', 180);
            $table->string('municipio', 120);
            $table->char('estado', 2);
            $table->json('endereco')->nullable();
            $table->string('email', 254)->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('status', 20)->default('ativo');
            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);
            $table->foreign('nucleo_id')->references('id')->on('nucleos')->restrictOnDelete();
            $table->unique(['nucleo_id', 'codigo'], 'uq_escolas_nucleo_codigo');
            $table->index(['nucleo_id', 'status'], 'idx_escolas_nucleo_status');
            $table->index(['nucleo_id', 'nome'], 'idx_escolas_nome');
        });

        DB::statement("ALTER TABLE escolas ADD CONSTRAINT ck_escolas_status CHECK (status IN ('ativo', 'inativo'))");
        DB::statement("ALTER TABLE escolas ADD CONSTRAINT ck_escolas_estado CHECK (estado REGEXP '^[A-Z]{2}$')");

        Schema::table('usuario_perfis', function (Blueprint $table) {
            $table->foreign('escola_id', 'fk_usuario_perfis_escola')->references('id')->on('escolas')->restrictOnDelete();
        });
        Schema::table('auditorias', function (Blueprint $table) {
            $table->foreign('escola_id', 'fk_auditorias_escola')->references('id')->on('escolas')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auditorias', fn (Blueprint $table) => $table->dropForeign('fk_auditorias_escola'));
        Schema::table('usuario_perfis', fn (Blueprint $table) => $table->dropForeign('fk_usuario_perfis_escola'));
        Schema::dropIfExists('escolas');
    }
};
