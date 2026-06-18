<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->date('data_nascimento')->nullable()->after('cpf');
            $table->string('telefone', 20)->nullable()->after('data_nascimento');
            $table->date('data_ingresso')->nullable()->after('telefone');
            $table->string('formacao_academica', 200)->nullable()->after('data_ingresso');
            $table->string('especializacao', 200)->nullable()->after('formacao_academica');
            $table->string('registro_profissional', 50)->nullable()->after('especializacao');
            $table->text('observacoes')->nullable()->after('registro_profissional');
            $table->boolean('forcar_troca_senha')->default(false)->after('observacoes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn([
                'data_nascimento',
                'telefone',
                'data_ingresso',
                'formacao_academica',
                'especializacao',
                'registro_profissional',
                'observacoes',
                'forcar_troca_senha',
            ]);
        });
    }
};
