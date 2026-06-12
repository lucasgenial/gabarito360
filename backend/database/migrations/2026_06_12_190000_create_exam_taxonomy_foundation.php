<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temas_habilidades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('disciplina_id');
            $table->string('codigo', 60)->nullable();
            $table->string('nome', 180);
            $table->text('descricao')->nullable();
            $table->string('tipo', 30);
            $table->boolean('ativo')->default(true);
            $table->timestamps(6);
            $table->foreign('disciplina_id')->references('id')->on('disciplinas')->restrictOnDelete();
            $table->unique(['disciplina_id', 'codigo'], 'uq_temas_disciplina_codigo');
            $table->index(['disciplina_id', 'ativo', 'nome'], 'idx_temas_disciplina_ativo_nome');
        });

        Schema::table('provas', function (Blueprint $table) {
            $table->uuid('disciplina_id')->nullable()->after('escola_id');
            $table->uuid('serie_ano_id')->nullable()->after('disciplina_id');
            $table->decimal('valor_total', 12, 4)->default(0)->after('alternativas');
            $table->foreign('disciplina_id')->references('id')->on('disciplinas')->restrictOnDelete();
            $table->foreign('serie_ano_id')->references('id')->on('series_anos')->restrictOnDelete();
            $table->index(['disciplina_id', 'serie_ano_id', 'status'], 'idx_provas_disciplina_serie_status');
            $table->index(['criado_por', 'status'], 'idx_provas_autor_status');
        });

        Schema::table('questoes', function (Blueprint $table) {
            $table->text('enunciado')->nullable()->after('codigo');
            $table->json('metadados')->nullable()->after('peso_padrao');
        });

        Schema::table('prova_turmas', function (Blueprint $table) {
            $table->string('status', 20)->default('ativo')->after('data_prevista');
            $table->index(['turma_id', 'status'], 'idx_prova_turmas_turma_status');
        });

        Schema::create('questao_temas', function (Blueprint $table) {
            $table->uuid('questao_id');
            $table->uuid('tema_habilidade_id');
            $table->boolean('principal')->default(false);
            $table->dateTime('created_at', 6)->useCurrent();
            $table->primary(['questao_id', 'tema_habilidade_id']);
            $table->foreign('questao_id')->references('id')->on('questoes')->cascadeOnDelete();
            $table->foreign('tema_habilidade_id')->references('id')->on('temas_habilidades')->restrictOnDelete();
            $table->index(['tema_habilidade_id', 'questao_id'], 'idx_questao_temas_tema_questao');
        });

        DB::statement("ALTER TABLE temas_habilidades ADD CONSTRAINT ck_temas_habilidades_tipo CHECK (tipo IN ('tema', 'habilidade'))");
        DB::statement('ALTER TABLE provas ADD CONSTRAINT ck_provas_valor_total CHECK (valor_total >= 0)');
        DB::statement("ALTER TABLE prova_turmas ADD CONSTRAINT ck_prova_turmas_status CHECK (status IN ('ativo', 'inativo'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('questao_temas');
        Schema::table('prova_turmas', function (Blueprint $table) {
            $table->dropIndex('idx_prova_turmas_turma_status');
            $table->dropColumn('status');
        });
        Schema::table('questoes', fn (Blueprint $table) => $table->dropColumn(['enunciado', 'metadados']));
        Schema::table('provas', function (Blueprint $table) {
            $table->dropForeign(['disciplina_id']);
            $table->dropForeign(['serie_ano_id']);
            $table->dropIndex('idx_provas_disciplina_serie_status');
            $table->dropIndex('idx_provas_autor_status');
            $table->dropColumn(['disciplina_id', 'serie_ano_id', 'valor_total']);
        });
        Schema::dropIfExists('temas_habilidades');
    }
};
