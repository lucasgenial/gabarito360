<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
    public function up(): void
    {
        DB::table('permissoes')->insertOrIgnore(['chave'=>'pedido.exportacao.autorizar','modulo'=>'titulares',
            'descricao'=>'Autorizar exportação delimitada do titular']);
        foreach (['admin_rede','professor_autonomo'] as $perfil) DB::table('perfil_permissoes')->insertOrIgnore([
            'perfil'=>$perfil,'permissao'=>'pedido.exportacao.autorizar','tipo'=>'padrao']);
        Schema::create('pedido_exportacao_autorizacoes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('pedido_id')->constrained('pedidos_titulares')->restrictOnDelete();
            $t->foreignId('aluno_id')->constrained('alunos')->restrictOnDelete();
            $t->unsignedInteger('sujeito_versao');$t->unsignedInteger('ciclo');$t->char('aluno_assinatura',64);
            $t->foreignId('aprovador_id')->constrained('usuarios')->restrictOnDelete();
            $t->foreignId('vinculo_id')->constrained('usuario_vinculos')->restrictOnDelete();$t->char('vinculo_assinatura',64);
            $t->foreignId('destinatario_acesso_id')->constrained('usuario_acessos_internos')->restrictOnDelete();
            $t->unsignedInteger('destinatario_versao');$t->string('formato',40);$t->text('campos');$t->text('evidencia');
            $t->dateTime('valida_ate');$t->dateTime('revogada_em')->nullable();$t->unsignedInteger('versao')->default(1);
            $t->dateTime('created_at');$t->index(['pedido_id','revogada_em','valida_ate'],'pedido_exportacao_vigencia_idx');
        });
        Schema::create('pedido_exportacao_eventos', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignUuid('autorizacao_id')->constrained('pedido_exportacao_autorizacoes')->restrictOnDelete();
            $t->foreignId('ator_id')->constrained('usuarios')->restrictOnDelete();
            $t->string('acao',30);$t->text('motivo');$t->dateTime('created_at');
        });
        Schema::create('pedido_exportacao_recibos', function (Blueprint $t) {
            $t->bigIncrements('id');$t->foreignId('vinculo_id')->constrained('usuario_vinculos')->restrictOnDelete();
            $t->char('vinculo_assinatura',64);$t->string('acao',60);$t->uuid('chave');$t->char('payload_hash',64);
            $t->text('resposta');$t->dateTime('created_at');
            $t->unique(['vinculo_id','vinculo_assinatura','acao','chave'],'pedido_exportacao_recibo_unico');
        });
    }
    public function down(): void { throw new RuntimeException('L4-C2: preservar concessões e auditoria; reversão exige procedimento próprio.'); }
};
