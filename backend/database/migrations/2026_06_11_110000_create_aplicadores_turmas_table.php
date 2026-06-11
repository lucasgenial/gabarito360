<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aplicadores_turmas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('turma_id');
            $table->uuid('usuario_id');
            $table->string('papel', 30);
            $table->date('inicio_em');
            $table->date('fim_em')->nullable();
            $table->uuid('vinculado_por')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('turma_id')->references('id')->on('turmas')->restrictOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('vinculado_por')->references('id')->on('usuarios')->nullOnDelete();
        });

        DB::statement("ALTER TABLE aplicadores_turmas ADD CONSTRAINT ck_aplicadores_turmas_papel CHECK (papel IN ('professor', 'aplicador', 'responsavel'))");
        DB::statement('ALTER TABLE aplicadores_turmas ADD CONSTRAINT ck_aplicadores_turmas_periodo CHECK (fim_em IS NULL OR fim_em >= inicio_em)');
        DB::statement('CREATE UNIQUE INDEX uq_aplicadores_turmas_ativo ON aplicadores_turmas (turma_id, usuario_id, papel) WHERE fim_em IS NULL');
        DB::statement('CREATE INDEX idx_aplicadores_turmas_usuario_ativo ON aplicadores_turmas (usuario_id, turma_id) WHERE fim_em IS NULL');
        DB::statement('CREATE INDEX idx_aplicadores_turmas_turma_ativo ON aplicadores_turmas (turma_id) WHERE fim_em IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('aplicadores_turmas');
    }
};
