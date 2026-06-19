<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assinatura_id')->constrained('assinaturas')->restrictOnDelete();
            $table->decimal('valor', 10, 2);
            $table->enum('status', ['pendente', 'aprovado', 'rejeitado', 'reembolsado'])->default('pendente');
            $table->enum('metodo', ['pix', 'boleto', 'cartao'])->nullable();
            $table->string('gateway_payment_id', 100)->nullable()->unique();
            $table->timestamp('pago_em')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('assinatura_id');
            $table->index('gateway_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
