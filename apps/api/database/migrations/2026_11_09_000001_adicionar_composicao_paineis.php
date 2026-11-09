<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('preferencias_paineis', fn (Blueprint $t) => $t->longText('composicao')->nullable());
    }
    public function down(): void { throw new RuntimeException('Preservar as composições pessoais e seu histórico.'); }
};
