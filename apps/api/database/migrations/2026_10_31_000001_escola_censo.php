<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arquivo: apps/api/database/migrations/2026_10_31_000001_escola_censo.php
 * Função: Cadastro de escola no mesmo modelo do ECOAR-MQA (docs/21, etapa 6):
 *         base pública do Censo Escolar (INEP) para consulta pelo código INEP,
 *         campos do Censo na escola, vínculo sede/anexo e código do órgão regional no núcleo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Base pública do Censo Escolar (Tabela_Escola), só consulta; importada por g360:censo-importar
        Schema::create('censo_escolas', function (Blueprint $table) {
            $table->char('codigo_inep', 8)->primary();
            $table->smallInteger('ano_censo');
            $table->string('nome', 200);
            $table->char('sg_uf', 2);
            $table->unsignedTinyInteger('co_uf');
            $table->unsignedInteger('co_municipio');
            $table->string('no_municipio', 100);
            $table->unsignedTinyInteger('tp_dependencia');          // 1 Federal, 2 Estadual, 3 Municipal, 4 Privada
            $table->unsignedTinyInteger('tp_localizacao')->nullable(); // 1 Urbana, 2 Rural
            $table->unsignedTinyInteger('tp_situacao')->nullable();    // 1 Ativa, 2 Paralisada, 3 Extinta
            $table->string('orgao_regional', 20)->nullable();        // Bahia: nº do NTE
            $table->string('endereco', 255)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->char('cep', 8)->nullable();
            $table->string('ddd', 3)->nullable();
            $table->string('telefone', 15)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['sg_uf', 'nome']);
            $table->index('co_municipio');
        });

        Schema::table('nucleos', function (Blueprint $table) {
            $table->string('orgao_regional', 20)->nullable()->after('nome')->index();
        });

        Schema::table('escolas', function (Blueprint $table) {
            $table->enum('tipo', ['sede', 'anexo', 'unidade_vinculada'])->default('sede')->after('tipo_rede');
            $table->foreignId('escola_sede_id')->nullable()->after('tipo')->constrained('escolas')->nullOnDelete();
            $table->enum('situacao_funcionamento', ['ativa', 'paralisada', 'extinta'])->nullable()->after('escola_sede_id');
            $table->enum('localizacao', ['urbana', 'rural'])->nullable()->after('situacao_funcionamento');
            $table->string('orgao_regional', 20)->nullable()->after('localizacao');
            $table->string('numero', 20)->nullable()->after('logradouro');
            $table->string('complemento', 100)->nullable()->after('numero');
            $table->string('bairro', 100)->nullable()->after('complemento');
            $table->char('cep', 8)->nullable()->after('bairro');
            $table->unsignedInteger('co_municipio')->nullable()->after('cidade');   // código IBGE
            $table->decimal('latitude', 10, 7)->nullable()->after('uf');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->json('turnos')->nullable()->after('email');
            $table->json('etapas')->nullable()->after('turnos');
            $table->json('modalidades')->nullable()->after('etapas');
            $table->enum('origem', ['censo', 'manual'])->default('manual')->after('modalidades');
            $table->smallInteger('ano_censo')->nullable()->after('origem');
            // Editada à mão: a reimportação do Censo não sobrescreve (mesma regra do ECOAR)
            $table->boolean('edicao_manual')->default(false)->after('ano_censo');
        });
    }

    public function down(): void
    {
        Schema::table('escolas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('escola_sede_id');
            $table->dropColumn(['tipo', 'situacao_funcionamento', 'localizacao', 'orgao_regional', 'numero', 'complemento',
                'bairro', 'cep', 'co_municipio', 'latitude', 'longitude', 'turnos', 'etapas', 'modalidades',
                'origem', 'ano_censo', 'edicao_manual']);
        });
        Schema::table('nucleos', function (Blueprint $table) {
            $table->dropColumn('orgao_regional');
        });
        Schema::dropIfExists('censo_escolas');
    }
};
