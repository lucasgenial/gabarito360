<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
return new class extends Migration {
    public function up(): void
    {
        \Illuminate\Support\Facades\Schema::table('copias_recebimentos',function(Blueprint $t) {
            $t->string('finalidade',32)->default('preparacao_material');
        });
    }
    public function down(): void { throw new RuntimeException('Preservar a finalidade das indicações.'); }
};
