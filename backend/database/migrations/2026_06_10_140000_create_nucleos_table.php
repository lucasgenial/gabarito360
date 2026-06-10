<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nucleos', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('codigo', 50);
            $table->string('nome', 180);
            $table->string('municipio', 120)->nullable();
            $table->char('estado', 2)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('status', 20)->default('ativo');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();
        });

        DB::statement('ALTER TABLE nucleos ALTER COLUMN email TYPE citext USING email::citext');
        DB::statement("ALTER TABLE nucleos ADD CONSTRAINT ck_nucleos_status CHECK (status IN ('ativo', 'inativo'))");
        DB::statement("ALTER TABLE nucleos ADD CONSTRAINT ck_nucleos_estado CHECK (estado IS NULL OR estado ~ '^[A-Z]{2}$')");
        DB::statement('CREATE UNIQUE INDEX uq_nucleos_codigo ON nucleos (lower(codigo)) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_nucleos_status ON nucleos (status) WHERE deleted_at IS NULL');

        Schema::table('usuario_perfis', function (Blueprint $table) {
            $table->foreign('nucleo_id', 'fk_usuario_perfis_nucleo')
                ->references('id')
                ->on('nucleos')
                ->restrictOnDelete();
        });

        Schema::table('auditorias', function (Blueprint $table) {
            $table->foreign('nucleo_id', 'fk_auditorias_nucleo')
                ->references('id')
                ->on('nucleos')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropForeign('fk_auditorias_nucleo');
        });

        Schema::table('usuario_perfis', function (Blueprint $table) {
            $table->dropForeign('fk_usuario_perfis_nucleo');
        });

        Schema::dropIfExists('nucleos');
    }
};
