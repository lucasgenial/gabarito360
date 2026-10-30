<?php

/**
 * Arquivo: apps/api/database/migrations/2026_10_30_000003_create_permissoes_tables.php
 * Função: Motor de permissões (docs/21, etapa 2): catálogo, padrão/extra por perfil,
 *         extras e retiradas por usuário e o registro de quem concedeu. Ao final,
 *         sincroniza o catálogo de App\Support\CatalogoPermissoes (docs/03 v2).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissoes', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 60)->unique();
            $table->string('modulo', 30);
            $table->string('descricao', 150);
            $table->timestamps();
        });

        // Padrão (vem com o perfil) ou extra (pode ser concedida a uma pessoa do perfil)
        Schema::create('perfil_permissoes', function (Blueprint $table) {
            $table->id();
            $table->string('perfil', 30);
            $table->string('permissao', 60);
            $table->enum('tipo', ['padrao', 'extra']);
            $table->unique(['perfil', 'permissao']);
        });

        // Extras concedidas e padrões retirados de uma pessoa específica
        Schema::create('usuario_permissoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('permissao', 60);
            $table->enum('efeito', ['conceder', 'retirar']);
            $table->date('valida_ate')->nullable();
            $table->foreignId('definida_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->unique(['usuario_id', 'permissao']);
        });

        // Registro de todas as mudanças (quem, para quem, o quê, quando)
        Schema::create('usuario_permissoes_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('permissao', 60);
            $table->enum('acao', ['conceder', 'retirar', 'remover']);
            $table->date('valida_ate')->nullable();
            $table->foreignId('feita_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Artisan::call('g360:permissoes-sincronizar');
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_permissoes_log');
        Schema::dropIfExists('usuario_permissoes');
        Schema::dropIfExists('perfil_permissoes');
        Schema::dropIfExists('permissoes');
    }
};
