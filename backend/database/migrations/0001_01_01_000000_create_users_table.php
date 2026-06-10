<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nome', 180);
            $table->string('email', 254);
            $table->string('documento', 30)->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('password');
            $table->string('status', 20)->default('ativo');
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('ultimo_acesso_at')->nullable();
            $table->rememberToken();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 254)->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
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

        DB::statement('ALTER TABLE usuarios ALTER COLUMN email TYPE citext USING email::citext');
        DB::statement('ALTER TABLE password_reset_tokens ALTER COLUMN email TYPE citext USING email::citext');
        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT ck_usuarios_status CHECK (status IN ('ativo', 'inativo', 'bloqueado'))");
        DB::statement('CREATE UNIQUE INDEX uq_usuarios_email ON usuarios (email) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_usuarios_status ON usuarios (status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_usuarios_documento ON usuarios (documento) WHERE documento IS NOT NULL AND deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
    }
};
