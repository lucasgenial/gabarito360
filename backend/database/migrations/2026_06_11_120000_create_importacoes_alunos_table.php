<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importacoes_alunos', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('escola_id');
            $table->uuid('turma_id');
            $table->uuid('solicitado_por')->nullable();
            $table->uuid('confirmado_por')->nullable();
            $table->string('status', 30);
            $table->string('arquivo_disco', 40);
            $table->string('arquivo_caminho', 500);
            $table->string('arquivo_nome', 255);
            $table->char('arquivo_checksum_sha256', 64);
            $table->jsonb('resumo');
            $table->jsonb('erros');
            $table->timestampTz('confirmado_at')->nullable();
            $table->timestampTz('processado_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('escola_id')->references('id')->on('escolas')->restrictOnDelete();
            $table->foreign('turma_id')->references('id')->on('turmas')->restrictOnDelete();
            $table->foreign('solicitado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('confirmado_por')->references('id')->on('usuarios')->nullOnDelete();
        });

        DB::statement("ALTER TABLE importacoes_alunos ADD CONSTRAINT ck_importacoes_alunos_status CHECK (status IN ('validada', 'com_erros', 'processando', 'concluida', 'falhou'))");
        DB::statement('CREATE INDEX idx_importacoes_alunos_escola_created ON importacoes_alunos (escola_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_importacoes_alunos_solicitante_created ON importacoes_alunos (solicitado_por, created_at DESC)');
        DB::statement("CREATE INDEX idx_importacoes_alunos_processando ON importacoes_alunos (status, confirmado_at) WHERE status = 'processando'");
    }

    public function down(): void
    {
        Schema::dropIfExists('importacoes_alunos');
    }
};
