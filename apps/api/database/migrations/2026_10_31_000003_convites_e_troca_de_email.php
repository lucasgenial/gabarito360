<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convite usa o mesmo token da recuperação, com validade própria
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->timestamp('expira_em')->nullable();
            $table->foreignId('convidado_por')->nullable()->constrained('usuarios')->nullOnDelete();
        });

        Schema::create('trocas_email', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->string('novo_email');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expira_em');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trocas_email');
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('convidado_por');
            $table->dropColumn('expira_em');
        });
    }
};
