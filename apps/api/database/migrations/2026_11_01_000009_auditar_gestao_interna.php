<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('acessos_internos_controle')) Schema::create('acessos_internos_controle', function (Blueprint $t) {
            $t->unsignedInteger('id')->primary();
            $t->timestamp('bootstrap_em')->nullable();
        });
        DB::table('acessos_internos_controle')->insertOrIgnore(['id'=>1]);
        if (!Schema::hasTable('acesso_interno_eventos')) Schema::create('acesso_interno_eventos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('acesso_id')->constrained('usuario_acessos_internos')->restrictOnDelete();
            $t->foreignId('ator_id')->nullable()->constrained('usuarios')->restrictOnDelete();
            $t->string('acao',24);
            $t->string('origem',16);
            $t->string('motivo',200);
            $t->json('anterior')->nullable();
            $t->json('atual');
            $t->timestamp('created_at');
            $t->index(['acesso_id','id']);
        });
    }
    public function down(): void { throw new RuntimeException('Preservar histórico e controle dos acessos internos.'); }
};
