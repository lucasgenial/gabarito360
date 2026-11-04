<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usuario_vinculos', fn (Blueprint $t) => $t->enum('perfil', [
            'admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aplicador', 'aluno',
            'professor_autonomo', 'secretario_escolar', 'vice_dir_escolar', 'auxiliar_secretaria',
        ])->change());
        Schema::table('usuarios', fn (Blueprint $t) => $t->enum('perfil', [
            'secretario_educacao', 'admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador',
            'professor', 'aplicador', 'aluno', 'secretario_escolar', 'vice_dir_escolar', 'auxiliar_secretaria',
        ])->change());
        Artisan::call('g360:permissoes-sincronizar');
    }
    public function down(): void
    {
        throw new RuntimeException('PF1: preservar identidades e vínculos; reversão exige procedimento próprio.');
    }
};
