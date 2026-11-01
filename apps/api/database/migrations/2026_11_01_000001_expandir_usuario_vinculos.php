<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expansão aditiva e retomável: nenhuma conta é convertida nesta migration.
        if (!Schema::hasTable('usuario_vinculos')) {
            Schema::create('usuario_vinculos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
                $t->foreignId('espaco_id')->constrained('espacos')->restrictOnDelete();
                $t->enum('perfil', ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aplicador', 'aluno']);
                $t->enum('situacao', ['convidado', 'ativo', 'suspenso', 'encerrado']);
                $t->dateTime('inicio_em');
                $t->dateTime('fim_em')->nullable();
                $t->unsignedBigInteger('versao_autorizacao')->default(1);
                $t->foreignId('criado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
                $t->timestamps();
                $t->unique(['usuario_id', 'espaco_id', 'perfil'], 'v1_pessoa_espaco_perfil');
                $t->unique(['espaco_id', 'id'], 'v1_espaco_vinculo');
                $t->index(['usuario_id', 'situacao', 'id']);
                $t->index(['espaco_id', 'perfil', 'situacao', 'id'], 'v1_espaco_perfil_situacao');
            });
        }
        if (!Schema::hasTable('vinculo_permissoes')) {
            Schema::create('vinculo_permissoes', function (Blueprint $t) {
                $t->id();
                $t->foreignId('vinculo_id')->constrained('usuario_vinculos')->restrictOnDelete();
                $t->string('permissao', 60);
                $t->foreign('permissao')->references('chave')->on('permissoes')->restrictOnDelete();
                $t->enum('efeito', ['conceder', 'retirar']);
                $t->date('valida_ate')->nullable();
                $t->foreignId('definida_por')->nullable()->constrained('usuarios')->restrictOnDelete();
                $t->timestamps();
                $t->unique(['vinculo_id', 'permissao']);
            });
        }
        if (!Schema::hasTable('vinculo_eventos')) {
            Schema::create('vinculo_eventos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('vinculo_id')->constrained('usuario_vinculos')->restrictOnDelete();
                $t->string('acao', 40);
                $t->foreignId('ator_id')->nullable()->constrained('usuarios')->restrictOnDelete();
                $t->dateTime('ocorrido_em');
                $t->string('motivo_tecnico', 80);
                $t->index(['vinculo_id', 'ocorrido_em', 'id']);
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('V1: reversão exige plano aprovado; não remover vínculos ou histórico automaticamente.');
    }
};
