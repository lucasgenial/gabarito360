<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('alunos', fn (Blueprint $table) => $table->unsignedTinyInteger('avatar_id')->nullable());
    }
    public function down(): void
    {
        Schema::table('alunos', fn (Blueprint $table) => $table->dropColumn('avatar_id'));
    }
};
