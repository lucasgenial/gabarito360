<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disciplina_area_vinculos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('espaco_id'); $t->unsignedBigInteger('disciplina_id'); $t->unsignedBigInteger('area_id');
            $t->boolean('ativo')->default(true); $t->unsignedInteger('versao')->default(1);
            $t->dateTime('vinculado_em')->nullable(); $t->dateTime('desvinculado_em')->nullable();
            $t->string('origem', 30)->default('registrada');
            $t->unique(['espaco_id', 'id'], 'dc1_periodo_escopo');
            $t->unique(['espaco_id','disciplina_id','area_id','id'], 'dc1_periodo_relacao');
            $t->index(['espaco_id', 'area_id', 'ativo'], 'dc1_periodo_lista');
            $t->foreign(['espaco_id', 'disciplina_id'])->references(['espaco_id', 'id'])->on('disciplinas')->restrictOnDelete();
            $t->foreign(['espaco_id', 'area_id'])->references(['espaco_id', 'id'])->on('areas_conhecimento')->restrictOnDelete();
        });
        Schema::table('disciplina_areas', function (Blueprint $t) {
            $t->unsignedBigInteger('vinculo_id')->nullable()->unique();
            $t->foreign(['espaco_id','disciplina_id','area_id','vinculo_id'],'dc1_corrente_periodo')->references(['espaco_id','disciplina_id','area_id','id'])->on('disciplina_area_vinculos')->restrictOnDelete();
        });
        // A data anterior não era registrada: não inventar um início histórico.
        DB::table('disciplina_areas')->orderBy('espaco_id')->orderBy('disciplina_id')->orderBy('area_id')->get()->each(function ($r) {
            $campos = ['espaco_id'=>$r->espaco_id, 'disciplina_id'=>$r->disciplina_id, 'area_id'=>$r->area_id];
            $id = DB::table('disciplina_area_vinculos')->insertGetId($campos + ['origem'=>'anterior_sem_data']);
            DB::table('disciplina_areas')->where($campos)->update(['vinculo_id'=>$id]);
        });
        Schema::table('provas', fn (Blueprint $t) => $t->unique(['espaco_id','id'], 'dc1_prova_classificacao_escopo'));
        Schema::create('prova_area_vinculos', function (Blueprint $t) {
            $t->unsignedBigInteger('espaco_id'); $t->unsignedBigInteger('prova_id'); $t->unsignedBigInteger('vinculo_id');
            $t->dateTime('registrado_em');
            $t->primary(['prova_id','vinculo_id']);
            $t->foreign(['espaco_id','prova_id'])->references(['espaco_id','id'])->on('provas')->restrictOnDelete();
            $t->foreign(['espaco_id','vinculo_id'])->references(['espaco_id','id'])->on('disciplina_area_vinculos')->restrictOnDelete();
        });
        // O período encerrado e as classificações históricas não podem ser reescritos.
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER dc1_periodo_fechado BEFORE UPDATE ON disciplina_area_vinculos WHEN OLD.ativo=0 OR NEW.ativo<>0 OR NEW.desvinculado_em IS NULL OR NEW.versao<>OLD.versao+1 OR NEW.desvinculado_em<OLD.vinculado_em OR NEW.espaco_id<>OLD.espaco_id OR NEW.disciplina_id<>OLD.disciplina_id OR NEW.area_id<>OLD.area_id OR NEW.vinculado_em IS NOT OLD.vinculado_em OR NEW.origem<>OLD.origem BEGIN SELECT RAISE(ABORT,'Período histórico protegido'); END");
            foreach (['disciplina_area_vinculos','prova_area_vinculos'] as $t) DB::unprepared("CREATE TRIGGER {$t}_sem_exclusao BEFORE DELETE ON {$t} BEGIN SELECT RAISE(ABORT,'Histórico protegido'); END");
            DB::unprepared("CREATE TRIGGER dc1_classificacao_sem_alteracao BEFORE UPDATE ON prova_area_vinculos BEGIN SELECT RAISE(ABORT,'Classificação histórica protegida'); END");
        } else {
            DB::unprepared("CREATE TRIGGER dc1_periodo_fechado BEFORE UPDATE ON disciplina_area_vinculos FOR EACH ROW BEGIN IF OLD.ativo=0 OR NEW.ativo<>0 OR NEW.desvinculado_em IS NULL OR NEW.versao<>OLD.versao+1 OR NEW.desvinculado_em<OLD.vinculado_em OR NEW.espaco_id<>OLD.espaco_id OR NEW.disciplina_id<>OLD.disciplina_id OR NEW.area_id<>OLD.area_id OR NOT(NEW.vinculado_em <=> OLD.vinculado_em) OR NEW.origem<>OLD.origem THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Período histórico protegido'; END IF; END");
            foreach (['disciplina_area_vinculos','prova_area_vinculos'] as $t) DB::unprepared("CREATE TRIGGER {$t}_sem_exclusao BEFORE DELETE ON {$t} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Histórico protegido'");
            DB::unprepared("CREATE TRIGGER dc1_classificacao_sem_alteracao BEFORE UPDATE ON prova_area_vinculos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Classificação histórica protegida'");
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Preservar períodos e classificações históricas.');
    }
};
