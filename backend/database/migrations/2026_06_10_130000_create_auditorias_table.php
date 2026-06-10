<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('request_id')->nullable();
            $table->uuid('usuario_id')->nullable();
            // FKs organizacionais serao adicionadas quando nucleos e escolas existirem.
            $table->uuid('nucleo_id')->nullable();
            $table->uuid('escola_id')->nullable();
            $table->string('acao', 100);
            $table->string('entidade_tipo', 100);
            $table->uuid('entidade_id')->nullable();
            $table->jsonb('dados_anteriores')->nullable();
            $table->jsonb('dados_novos')->nullable();
            $table->jsonb('metadados')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
        });

        DB::statement('ALTER TABLE auditorias ALTER COLUMN ip TYPE inet USING ip::inet');
        DB::statement('CREATE INDEX idx_auditorias_entidade ON auditorias (entidade_tipo, entidade_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_auditorias_usuario_data ON auditorias (usuario_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_auditorias_acao_data ON auditorias (acao, created_at DESC)');
        DB::statement('CREATE INDEX idx_auditorias_nucleo_data ON auditorias (nucleo_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_auditorias_escola_data ON auditorias (escola_id, created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
