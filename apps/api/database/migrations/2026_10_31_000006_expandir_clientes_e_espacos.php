<?php

use App\Support\EstruturaEspacos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('clientes')) {
            Schema::create('clientes', function (Blueprint $t) {
                $t->id();
                $t->string('chave_origem', 80)->nullable()->unique();
                $t->string('tipo', 24);
                $t->string('nome', 200);
                $t->text('documento')->nullable();
                $t->char('documento_hash', 64)->nullable();
                $t->string('modalidade', 24);
                $t->string('situacao', 24);
                $t->foreignId('criado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
                $t->timestamps();
                $t->index(['situacao', 'id']);
                $t->index(['tipo', 'documento_hash']);
            });
        }
        if (!Schema::hasTable('espacos')) {
            Schema::create('espacos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
                $t->string('tipo', 24);
                $t->string('nome', 200);
                $t->string('situacao', 24);
                $t->foreignId('rede_id')->nullable()->unique()->constrained('redes')->restrictOnDelete();
                $t->foreignId('nucleo_id')->nullable()->unique()->constrained('nucleos')->restrictOnDelete();
                $t->foreignId('escola_id')->nullable()->unique()->constrained('escolas')->restrictOnDelete();
                $t->foreignId('usuario_proprietario_id')->nullable()->unique()->constrained('usuarios')->restrictOnDelete();
                $t->foreignId('criado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
                $t->timestamps();
                $t->index(['cliente_id', 'situacao', 'id']);
                $t->index(['tipo', 'situacao', 'id']);
            });
        }
        // Passos independentes para retomar DDL parcialmente aplicado no MariaDB.
        foreach (EstruturaEspacos::TABELAS as $tabela) {
            foreach (['espaco_id', 'criado_por'] as $campo) {
                if (!Schema::hasColumn($tabela, $campo)) {
                    Schema::table($tabela, fn (Blueprint $t) => $t->unsignedBigInteger($campo)->nullable());
                }
            }
            if (!Schema::hasColumn($tabela, 'origem_autoria')) {
                Schema::table($tabela, fn (Blueprint $t) => $t->string('origem_autoria', 24)->nullable());
            }
            if (!Schema::hasIndex($tabela, $tabela.'_espaco_id_index')) {
                Schema::table($tabela, fn (Blueprint $t) => $t->index('espaco_id'));
            }
            // FKs de autoria existente permanecem; FKs adicionais e compostas são T2-E.
        }
    }

    public function down(): void
    {
        throw new RuntimeException('T2: reversão exige plano aprovado; não remover espaços ou autoria automaticamente.');
    }
};
