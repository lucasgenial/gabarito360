<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sem o corpo da mensagem e sem o endereço em texto puro (docs/33, RL9)
        Schema::create('emails_enviados', function (Blueprint $table) {
            $table->id();
            $table->string('modelo', 60);
            $table->char('destinatario_hash', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['modelo', 'created_at']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('assunto', 120)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('assunto');
        });
        Schema::dropIfExists('emails_enviados');
    }
};
