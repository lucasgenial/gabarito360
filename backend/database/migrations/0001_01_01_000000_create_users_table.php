<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome', 180);
            $table->string('email', 254)->unique('uq_usuarios_email');
            $table->string('documento', 30)->nullable()->index('idx_usuarios_documento');
            $table->string('telefone', 30)->nullable();
            $table->string('password');
            $table->string('status', 20)->default('ativo')->index('idx_usuarios_status');
            $table->dateTime('email_verified_at', 6)->nullable();
            $table->dateTime('ultimo_acesso_at', 6)->nullable();
            $table->rememberToken();
            $table->dateTime('created_at', 6)->useCurrent();
            $table->dateTime('updated_at', 6)->useCurrent();
            $table->softDeletes('deleted_at', 6);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 254)->primary();
            $table->string('token');
            $table->dateTime('created_at', 6)->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            $table->foreign('user_id')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
    }
};
