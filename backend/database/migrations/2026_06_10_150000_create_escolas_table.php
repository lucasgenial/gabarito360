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
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('nucleo_id');
            $table->string('codigo', 50);
            $table->string('nome', 180);
            $table->string('municipio', 120);
            $table->char('estado', 2);
            $table->jsonb('endereco')->nullable();
            $table->string('email', 254)->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('status', 20)->default('ativo');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->foreign('nucleo_id')->references('id')->on('nucleos')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE escolas ALTER COLUMN email TYPE citext USING email::citext');
        DB::statement("ALTER TABLE escolas ADD CONSTRAINT ck_escolas_status CHECK (status IN ('ativo', 'inativo'))");
        DB::statement("ALTER TABLE escolas ADD CONSTRAINT ck_escolas_estado CHECK (estado ~ '^[A-Z]{2}$')");
        DB::statement('CREATE UNIQUE INDEX uq_escolas_nucleo_codigo ON escolas (nucleo_id, lower(codigo)) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_escolas_nucleo_status ON escolas (nucleo_id, status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_escolas_nome ON escolas (nucleo_id, nome) WHERE deleted_at IS NULL');

        Schema::table('usuario_perfis', function (Blueprint $table) {
            $table->foreign('escola_id', 'fk_usuario_perfis_escola')
                ->references('id')
                ->on('escolas')
                ->restrictOnDelete();
        });

        Schema::table('auditorias', function (Blueprint $table) {
            $table->foreign('escola_id', 'fk_auditorias_escola')
                ->references('id')
                ->on('escolas')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropForeign('fk_auditorias_escola');
        });

        Schema::table('usuario_perfis', function (Blueprint $table) {
            $table->dropForeign('fk_usuario_perfis_escola');
        });

        Schema::dropIfExists('escolas');
    }
};
