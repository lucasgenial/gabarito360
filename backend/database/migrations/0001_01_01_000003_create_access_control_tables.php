<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfis', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('codigo', 60);
            $table->string('nome', 120);
            $table->text('descricao')->nullable();
            $table->string('escopo_permitido', 20);
            $table->boolean('sistema')->default(false);
            $table->string('status', 20)->default('ativo');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('status', 'idx_perfis_status');
        });

        Schema::create('permissoes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('codigo', 100);
            $table->text('descricao')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('perfil_permissoes', function (Blueprint $table) {
            $table->uuid('perfil_id');
            $table->uuid('permissao_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['perfil_id', 'permissao_id']);
            $table->foreign('perfil_id')->references('id')->on('perfis')->cascadeOnDelete();
            $table->foreign('permissao_id')->references('id')->on('permissoes')->cascadeOnDelete();
        });

        Schema::create('usuario_perfis', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('usuario_id');
            $table->uuid('perfil_id');
            // FKs organizacionais serao adicionadas quando nucleos e escolas existirem.
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->uuid('concedido_por')->nullable();
            $table->timestampTz('inicio_at')->useCurrent();
            $table->timestampTz('fim_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('usuario_id')->references('id')->on('usuarios');
            $table->foreign('perfil_id')->references('id')->on('perfis');
            $table->foreign('concedido_por')->references('id')->on('usuarios')->nullOnDelete();
        });

        DB::statement("ALTER TABLE perfis ADD CONSTRAINT ck_perfis_escopo_permitido CHECK (escopo_permitido IN ('global', 'nucleo', 'escola', 'operacional'))");
        DB::statement("ALTER TABLE perfis ADD CONSTRAINT ck_perfis_status CHECK (status IN ('ativo', 'inativo'))");
        DB::statement('CREATE UNIQUE INDEX uq_perfis_codigo ON perfis (lower(codigo))');
        DB::statement('CREATE UNIQUE INDEX uq_permissoes_codigo ON permissoes (lower(codigo))');

        DB::statement('ALTER TABLE usuario_perfis ADD CONSTRAINT ck_usuario_perfis_escopo CHECK (num_nonnulls(nucleo_id, escola_id) <= 1)');
        DB::statement('ALTER TABLE usuario_perfis ADD CONSTRAINT ck_usuario_perfis_periodo CHECK (fim_at IS NULL OR fim_at >= inicio_at)');
        DB::statement("CREATE UNIQUE INDEX uq_usuario_perfis_ativo ON usuario_perfis (usuario_id, perfil_id, COALESCE(nucleo_id, '00000000-0000-0000-0000-000000000000'::uuid), COALESCE(escola_id, '00000000-0000-0000-0000-000000000000'::uuid)) WHERE fim_at IS NULL");
        DB::statement('CREATE INDEX idx_usuario_perfis_usuario_ativo ON usuario_perfis (usuario_id) WHERE fim_at IS NULL');
        DB::statement('CREATE INDEX idx_usuario_perfis_nucleo ON usuario_perfis (nucleo_id, perfil_id) WHERE fim_at IS NULL');
        DB::statement('CREATE INDEX idx_usuario_perfis_escola ON usuario_perfis (escola_id, perfil_id) WHERE fim_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_perfis');
        Schema::dropIfExists('perfil_permissoes');
        Schema::dropIfExists('permissoes');
        Schema::dropIfExists('perfis');
    }
};
