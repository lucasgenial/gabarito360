<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 200);
            $table->enum('tipo', ['municipal', 'estadual', 'federal']);
            $table->char('uf', 2);
            $table->decimal('meta_media', 4, 2)->default(7.00);
            $table->decimal('meta_minima', 4, 2)->default(6.00);
            $table->integer('limiar_seges_min')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redes');
    }
};
