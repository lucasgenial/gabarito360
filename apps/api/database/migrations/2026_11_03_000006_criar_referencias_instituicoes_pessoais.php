<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        foreach(['catalogo.consultar'=>'Consultar referências públicas institucionais','catalogo.referencia_pessoal.gerir'=>'Organizar instituições declaradas no próprio espaço pessoal'] as $p=>$d) {
            DB::table('permissoes')->insertOrIgnore(['chave'=>$p,'modulo'=>'catalogo','descricao'=>$d]);
            DB::table('perfil_permissoes')->insertOrIgnore(['perfil'=>'professor_autonomo','permissao'=>$p,'tipo'=>'padrao']);
        }
        Schema::table('espacos',fn(Blueprint $t)=>$t->unique(['id','usuario_proprietario_id'],'cat_ref_proprietario_unique'));
        Schema::table('usuario_vinculos',fn(Blueprint $t)=>$t->unique(['id','usuario_id','espaco_id'],'cat_ref_vinculo_unique'));
        Schema::table('catalogo_entradas',fn(Blueprint $t)=>$t->unique(['id','publicacao_id','unidade_id'],'cat_ref_entrada_unique'));
        Schema::create('referencias_instituicoes_pessoais',function(Blueprint $t){
            $t->uuid('id')->primary();$t->unsignedBigInteger('espaco_id');$t->unsignedBigInteger('usuario_id');$t->unsignedBigInteger('vinculo_id');
            $t->uuid('unidade_id');$t->unsignedBigInteger('entrada_id');$t->uuid('publicacao_id');
            $t->string('situacao',12);$t->unsignedInteger('versao');$t->timestamps();
            $t->unique(['espaco_id','unidade_id'],'cat_ref_pessoal_unique');$t->index(['espaco_id','usuario_id','situacao'],'cat_ref_lista_idx');
            $t->foreign(['espaco_id','usuario_id'],'cat_ref_dono_fk')->references(['id','usuario_proprietario_id'])->on('espacos')->restrictOnDelete();
            $t->foreign(['vinculo_id','usuario_id','espaco_id'],'cat_ref_vinculo_fk')->references(['id','usuario_id','espaco_id'])->on('usuario_vinculos')->restrictOnDelete();
            $t->foreign(['entrada_id','publicacao_id','unidade_id'],'cat_ref_entrada_fk')->references(['id','publicacao_id','unidade_id'])->on('catalogo_entradas')->restrictOnDelete();
        });
        Schema::create('catalogo_referencia_eventos',function(Blueprint $t){
            $t->bigIncrements('id');$t->foreignUuid('referencia_id')->constrained('referencias_instituicoes_pessoais')->restrictOnDelete();
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();$t->foreignId('vinculo_id')->constrained('usuario_vinculos')->restrictOnDelete();
            $t->string('acao',20);$t->unsignedInteger('versao');$t->uuid('anterior_id')->nullable();$t->uuid('publicacao_id');$t->dateTime('ocorrido_em');
            $t->unique(['referencia_id','versao'],'cat_ref_evento_versao_unique');
            $t->foreign('publicacao_id')->references('id')->on('catalogo_publicacoes')->restrictOnDelete();
            $t->foreign('anterior_id')->references('id')->on('catalogo_publicacoes')->restrictOnDelete();
        });
        $sqlite=DB::connection()->getDriverName()==='sqlite';
        foreach(['UPDATE','DELETE'] as $acao) DB::unprepared($sqlite
            ? "CREATE TRIGGER cat_ref_eventos_{$acao} BEFORE {$acao} ON catalogo_referencia_eventos BEGIN SELECT RAISE(ABORT,'Evento imutavel'); END"
            : "CREATE TRIGGER cat_ref_eventos_{$acao} BEFORE {$acao} ON catalogo_referencia_eventos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Evento imutavel'");
        $cond=$sqlite ? 'NEW.id IS NOT OLD.id OR NEW.espaco_id IS NOT OLD.espaco_id OR NEW.usuario_id IS NOT OLD.usuario_id OR NEW.vinculo_id IS NOT OLD.vinculo_id OR NEW.unidade_id IS NOT OLD.unidade_id'
            : 'NEW.id <> OLD.id OR NEW.espaco_id <> OLD.espaco_id OR NEW.usuario_id <> OLD.usuario_id OR NEW.vinculo_id <> OLD.vinculo_id OR NEW.unidade_id <> OLD.unidade_id';
        DB::unprepared($sqlite
            ? "CREATE TRIGGER cat_ref_identidade BEFORE UPDATE ON referencias_instituicoes_pessoais WHEN {$cond} BEGIN SELECT RAISE(ABORT,'Referencia nao pode ser reatribuida'); END"
            : "CREATE TRIGGER cat_ref_identidade BEFORE UPDATE ON referencias_instituicoes_pessoais FOR EACH ROW BEGIN IF {$cond} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Referencia nao pode ser reatribuida'; END IF; END");
        DB::unprepared($sqlite
            ? "CREATE TRIGGER cat_ref_preservar BEFORE DELETE ON referencias_instituicoes_pessoais BEGIN SELECT RAISE(ABORT,'Arquivar preservando historico'); END"
            : "CREATE TRIGGER cat_ref_preservar BEFORE DELETE ON referencias_instituicoes_pessoais FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Arquivar preservando historico'");
    }
    public function down(): void { throw new RuntimeException('Preservar declarações e eventos; reversão exige procedimento próprio.'); }
};
