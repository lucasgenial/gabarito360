<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('titular_tipo', 40);
            $table->uuid('titular_id')->nullable();
            $table->string('finalidade', 80);
            $table->boolean('concedido')->default(true);
            $table->string('versao_termo', 20)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('concedido_em', 6)->useCurrent();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->index(['titular_tipo', 'titular_id'], 'idx_consentimentos_titular');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimentos');
    }
};
