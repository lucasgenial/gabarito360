<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        DB::table('catalogo_fontes')->insert(['nome'=>'manual','publicacao_id'=>null]);
        foreach(['catalogo.solicitar'=>'Solicitar cadastro no próprio espaço pessoal','catalogo.manter_escopo'=>'Manter unidades com autoridade institucional comprovada'] as $p=>$d) {
            DB::table('permissoes')->insertOrIgnore(['chave'=>$p,'modulo'=>'catalogo','descricao'=>$d]);
            foreach($p==='catalogo.solicitar'?['professor_autonomo']:['admin_rede','dir_nucleo','dir_escolar'] as $perfil)
                DB::table('perfil_permissoes')->insertOrIgnore(['perfil'=>$perfil,'permissao'=>$p,'tipo'=>'padrao']);
        }
        Schema::create('catalogo_solicitacoes',function(Blueprint $t){
            $t->uuid('id')->primary();$t->unsignedBigInteger('usuario_id');$t->unsignedBigInteger('espaco_id');$t->unsignedBigInteger('vinculo_id');
            $t->uuid('chave');$t->char('payload_hash',64);$t->text('dados');$t->string('situacao',30);$t->unsignedInteger('versao');
            $t->uuid('unidade_id')->nullable();$t->uuid('publicacao_id')->nullable();$t->timestamps();
            $t->unique(['usuario_id','espaco_id','chave'],'cat_sol_intencao_unique');$t->index(['espaco_id','usuario_id','created_at'],'cat_sol_proprio_idx');$t->index(['situacao','created_at'],'cat_sol_fila_idx');
            $t->foreign(['espaco_id','usuario_id'],'cat_sol_dono_fk')->references(['id','usuario_proprietario_id'])->on('espacos')->restrictOnDelete();
            $t->foreign(['vinculo_id','usuario_id','espaco_id'],'cat_sol_vinculo_fk')->references(['id','usuario_id','espaco_id'])->on('usuario_vinculos')->restrictOnDelete();
            $t->foreign('unidade_id')->references('id')->on('catalogo_unidades')->restrictOnDelete();$t->foreign('publicacao_id')->references('id')->on('catalogo_publicacoes')->restrictOnDelete();
        });
        Schema::create('catalogo_solicitacao_eventos',function(Blueprint $t){
            $t->bigIncrements('id');$t->foreignUuid('solicitacao_id')->constrained('catalogo_solicitacoes')->restrictOnDelete();
            $t->unsignedInteger('versao');$t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();$t->unsignedBigInteger('vinculo_id')->nullable();$t->unsignedBigInteger('acesso_id')->nullable();
            $t->string('acao',30);$t->text('mensagem');$t->text('detalhes');$t->char('payload_hash',64);$t->dateTime('ocorrido_em');$t->unique(['solicitacao_id','versao'],'cat_sol_evento_unique');
        });
        Schema::create('catalogo_solicitacao_avisos',function(Blueprint $t){
            $t->bigIncrements('id');$t->foreignUuid('solicitacao_id')->constrained('catalogo_solicitacoes')->restrictOnDelete();
            $t->unsignedInteger('versao');$t->string('situacao',30);$t->dateTime('created_at');$t->unique(['solicitacao_id','versao'],'cat_sol_aviso_unique');
        });
        Schema::create('catalogo_gestao_autoridades',function(Blueprint $t){
            $t->bigIncrements('id');$t->foreignId('escola_id')->constrained('escolas')->restrictOnDelete();$t->foreignUuid('unidade_id')->constrained('catalogo_unidades')->restrictOnDelete();
            $t->string('situacao',12);$t->unsignedInteger('versao');$t->unique(['escola_id','unidade_id'],'cat_autoridade_unique');
        });
        Schema::create('catalogo_manutencao_eventos',function(Blueprint $t){
            $t->bigIncrements('id');$t->foreignUuid('unidade_id')->constrained('catalogo_unidades')->restrictOnDelete();$t->foreignUuid('publicacao_id')->nullable()->constrained('catalogo_publicacoes')->restrictOnDelete();
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();$t->unsignedBigInteger('vinculo_id')->nullable();$t->unsignedBigInteger('acesso_id')->nullable();
            $t->foreignId('escola_id')->nullable()->constrained('escolas')->restrictOnDelete();$t->string('acao',30);$t->text('evidencia');$t->dateTime('ocorrido_em');
        });
        $sqlite=DB::connection()->getDriverName()==='sqlite';
        foreach(['catalogo_solicitacao_eventos','catalogo_solicitacao_avisos','catalogo_manutencao_eventos'] as $tabela) foreach(['UPDATE','DELETE'] as $acao) {
            $nome=substr($tabela,0,38).'_'.strtolower($acao);
            DB::unprepared($sqlite?"CREATE TRIGGER {$nome} BEFORE {$acao} ON {$tabela} BEGIN SELECT RAISE(ABORT,'Historico imutavel'); END":"CREATE TRIGGER {$nome} BEFORE {$acao} ON {$tabela} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Historico imutavel'");
        }
        $cond=$sqlite?'NEW.id IS NOT OLD.id OR NEW.usuario_id IS NOT OLD.usuario_id OR NEW.espaco_id IS NOT OLD.espaco_id OR NEW.vinculo_id IS NOT OLD.vinculo_id OR NEW.chave IS NOT OLD.chave OR NEW.payload_hash IS NOT OLD.payload_hash':'NEW.id <> OLD.id OR NEW.usuario_id <> OLD.usuario_id OR NEW.espaco_id <> OLD.espaco_id OR NEW.vinculo_id <> OLD.vinculo_id OR NEW.chave <> OLD.chave OR NEW.payload_hash <> OLD.payload_hash';
        DB::unprepared($sqlite?"CREATE TRIGGER cat_sol_identidade BEFORE UPDATE ON catalogo_solicitacoes WHEN {$cond} BEGIN SELECT RAISE(ABORT,'Protocolo nao pode ser reatribuido'); END":"CREATE TRIGGER cat_sol_identidade BEFORE UPDATE ON catalogo_solicitacoes FOR EACH ROW BEGIN IF {$cond} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Protocolo nao pode ser reatribuido'; END IF; END");
        DB::unprepared($sqlite?"CREATE TRIGGER cat_sol_preservar BEFORE DELETE ON catalogo_solicitacoes BEGIN SELECT RAISE(ABORT,'Preservar protocolo'); END":"CREATE TRIGGER cat_sol_preservar BEFORE DELETE ON catalogo_solicitacoes FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Preservar protocolo'");
    }
    public function down(): void { throw new RuntimeException('Preservar protocolos e historico; reversao exige procedimento proprio.'); }
};
