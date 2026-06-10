<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos_mobile', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('usuario_id');
            $table->string('identificador', 180);
            $table->string('plataforma', 30)->default('android');
            $table->string('modelo_dispositivo', 120)->nullable();
            $table->string('versao_sistema', 50)->nullable();
            $table->string('versao_app', 30);
            $table->timestampTz('ultimo_acesso_at')->nullable();
            $table->timestampTz('revogado_at')->nullable();
            $table->timestampsTz();

            $table->foreign('usuario_id')->references('id')->on('usuarios');
            $table->unique(['usuario_id', 'identificador'], 'uq_dispositivos_mobile_usuario_identificador');
            $table->index(['usuario_id', 'revogado_at'], 'idx_dispositivos_mobile_usuario_revogado');
        });

        DB::statement("ALTER TABLE dispositivos_mobile ADD CONSTRAINT ck_dispositivos_mobile_plataforma CHECK (plataforma IN ('android'))");

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('dispositivo_mobile_id')->nullable()->after('tokenable_id');
            $table->foreign('dispositivo_mobile_id')
                ->references('id')
                ->on('dispositivos_mobile')
                ->cascadeOnDelete();
            $table->index('dispositivo_mobile_id', 'idx_personal_access_tokens_dispositivo_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropForeign(['dispositivo_mobile_id']);
            $table->dropIndex('idx_personal_access_tokens_dispositivo_mobile');
            $table->dropColumn('dispositivo_mobile_id');
        });

        Schema::dropIfExists('dispositivos_mobile');
    }
};
