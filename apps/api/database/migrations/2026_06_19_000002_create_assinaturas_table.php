<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plano_id')->constrained('planos')->restrictOnDelete();
            $table->foreignId('titular_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('rede_id')->nullable()->constrained('redes')->nullOnDelete();
            $table->foreignId('secretaria_id')->nullable()->constrained('secretarias')->nullOnDelete();
            $table->unsignedInteger('licencas_contratadas')->default(1);
            $table->enum('status', ['trial', 'ativa', 'atrasada', 'cancelada'])->default('trial');
            $table->string('gateway_subscription_id', 100)->nullable();
            $table->timestamp('trial_termina_em')->nullable();
            $table->timestamp('proxima_cobranca_em')->nullable();
            $table->timestamps();

            $table->index('titular_id');
            $table->index('rede_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assinaturas');
    }
};
