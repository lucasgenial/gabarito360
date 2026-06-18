<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redes', function (Blueprint $table) {
            $table->foreignId('secretaria_id')->nullable()->after('id')->constrained('secretarias')->nullOnDelete();
            $table->enum('modalidade', ['institucional', 'individual'])->default('institucional')->after('uf');
            $table->foreignId('usuario_titular_id')->nullable()->after('modalidade')->constrained('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('redes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('secretaria_id');
            $table->dropConstrainedForeignId('usuario_titular_id');
            $table->dropColumn('modalidade');
        });
    }
};
