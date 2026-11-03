<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('escolas', fn (Blueprint $t) => $t->string('sigla',40)->nullable());
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar siglas cadastradas; reversão exige procedimento próprio.');
    }
};
