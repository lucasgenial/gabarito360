<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Services\{CatalogoSelecao,PrepararCatalogoInstituicoes,PublicarCatalogoInstituicoes,ManutencaoCatalogo,SolicitacoesCatalogo,ReferenciasInstituicoesPessoais};
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\ArquivosCatalogo;
use Tests\TestCase;

class SolicitacoesCatalogoTest extends TestCase
{
    use RefreshDatabase,ArquivosCatalogo;
    private array $fonte;
    protected function setUp(): void
    {
        parent::setUp();config(['catalogo.interface_ativa'=>true,'espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'acessos_internos.ativo'=>true]);Permissao::limparCache();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        foreach(range(1,5) as $id)Usuario::forceCreate(['id'=>$id,'nome'=>'Pessoa fictícia','perfil'=>'professor','email'=>"sol$id@example.test",'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
        foreach([1,2] as $id){
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Teste','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        }
        foreach([3=>'suporte',4=>'desenvolvedor',5=>'financeiro'] as $id=>$perfil)DB::table('usuario_acessos_internos')->insert(['id'=>$id,'usuario_id'=>$id,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        $this->fonte=$this->fonteCatalogo('basica');$this->promover();$this->entrar(1);
    }
    protected function tearDown(): void {$this->limparArquivosCatalogo();parent::tearDown();}
    private function promover(?string $anterior=null): void {
        $this->fonte['sha256']=hash_file('sha256',$this->fonte['arquivo']);$r=app(PrepararCatalogoInstituicoes::class)->executar(...$this->fonte);
        app(PublicarCatalogoInstituicoes::class)->executar($r['importacao_id'],$this->fonte['ufs'],substituir:$anterior,motivo:$anterior?'Revisão fictícia':null);
    }
    private function ator(int $id): Usuario {return $id<=2?Usuario::find($id)->noVinculo($id):Usuario::find($id)->noAcessoInterno($id,1);}
    private function entrar(int $id): void {Sanctum::actingAs($this->ator($id));}
    private function cadastro(): array {return ['nome'=>'Instituto do Teste Fictício','sigla'=>'ITF','tipo'=>'instituto','uf'=>'BA','municipio_ibge'=>'2900001','dependencia'=>'privada','site'=>'https://example.test/instituicao','observacao'=>'Pedido privado fictício'];}
    private function criar(): string { $this->entrar(1);return $this->postJson('/api/v1/catalogo/minhas-solicitacoes',['chave'=>(string)Str::uuid(),'cadastro'=>$this->cadastro()])->assertOk()->json('data.id'); }
    private function analisar(string $id): void {$this->entrar(3);$this->postJson("/api/v1/catalogo/atendimento/$id/acoes",['versao'=>1,'acao'=>'analisar'])->assertOk();}
    public function test_protocolo_privado_idempotente_e_fila_somente_interna(): void {
        $d=['chave'=>(string)Str::uuid(),'cadastro'=>$this->cadastro()];
        $id=$this->postJson('/api/v1/catalogo/minhas-solicitacoes',$d)->assertOk()->json('data.id');
        $this->postJson('/api/v1/catalogo/minhas-solicitacoes',$d)->assertOk()->assertJsonPath('data.id',$id);
        $d['cadastro']['nome']='Outro nome';$this->postJson('/api/v1/catalogo/minhas-solicitacoes',$d)->assertConflict();
        $this->assertStringNotContainsString('Pedido privado',DB::table('catalogo_solicitacoes')->value('dados'));
        $this->entrar(2);$this->getJson("/api/v1/catalogo/minhas-solicitacoes/$id")->assertNotFound();$this->getJson('/api/v1/catalogo/minhas-solicitacoes')->assertOk()->assertJsonCount(0,'data.itens');
        $this->getJson('/api/v1/catalogo/atendimento')->assertForbidden();
        $this->entrar(5);$this->getJson('/api/v1/catalogo/atendimento')->assertForbidden();
        $this->entrar(3);$this->getJson('/api/v1/catalogo/atendimento')->assertOk()->assertJsonCount(1,'data.itens');
        $this->getJson("/api/v1/catalogo/atendimento/$id")->assertOk()->assertJsonPath('data.dados.observacao','Pedido privado fictício');
        $this->getJson('/api/v1/alunos')->assertForbidden();$this->assertDatabaseCount('catalogo_solicitacao_eventos',1);$this->assertDatabaseCount('catalogo_solicitacao_avisos',1);
    }
    public function test_complementacao_no_mesmo_protocolo_e_versao_obsoleta(): void {
        $id=$this->criar();$this->analisar($id);
        $d=['versao'=>2,'acao'=>'pedir_complemento','mensagem'=>'Informe o nome completo da unidade.'];
        $this->postJson("/api/v1/catalogo/atendimento/$id/acoes",$d)->assertOk()->assertJsonPath('data.versao',3);
        $this->postJson("/api/v1/catalogo/atendimento/$id/acoes",$d)->assertOk()->assertJsonPath('data.versao',3);
        $this->entrar(1);$c=$this->cadastro();$c['nome']='Instituto fictício completo';
        $this->postJson("/api/v1/catalogo/minhas-solicitacoes/$id/acoes",['versao'=>2,'acao'=>'complementar','cadastro'=>$c])->assertConflict();
        $this->postJson("/api/v1/catalogo/minhas-solicitacoes/$id/acoes",['versao'=>3,'acao'=>'complementar','cadastro'=>$c])->assertOk()->assertJsonPath('data.situacao','em_analise');
        $this->getJson("/api/v1/catalogo/minhas-solicitacoes/$id")->assertOk()->assertJsonPath('data.dados.nome',$c['nome'])->assertJsonCount(4,'data.avisos');
        $this->assertDatabaseCount('catalogo_solicitacoes',1);
    }
    public function test_resolver_existente_nao_cria_vinculo_ou_altera_gabarito(): void {
        DB::table('provas')->insert(['id'=>1,'espaco_id'=>1,'espaco_tipo'=>'pessoal','criado_por'=>1,'titulo'=>'Preservar','disciplina'=>'Teste','serie'=>'1','num_questoes'=>1,'num_alternativas'=>5]);
        DB::table('gabaritos')->insert(['id'=>1,'prova_id'=>1,'espaco_id'=>1]);DB::table('gabarito_questoes')->insert(['gabarito_id'=>1,'espaco_id'=>1,'numero_questao'=>1,'alternativa'=>'D','peso'=>2]);
        $antes=[];foreach(['escolas','espacos','usuario_vinculos','provas','gabaritos','gabarito_questoes','alunos','notas'] as $t)$antes[$t]=DB::table($t)->get()->toJson();
        $id=$this->criar();$this->analisar($id);$e=app(CatalogoSelecao::class)->buscar(['uf'=>'BA'])['itens'][0];
        $d=['versao'=>2,'acao'=>'aprovar_existente','unidade_id'=>$e['unidade_id'],'edicao_id'=>$e['edicao_id'],'motivo'=>'Código e localização conferidos','conferencia'=>true];
        $this->postJson("/api/v1/catalogo/atendimento/$id/acoes",$d)->assertOk()->assertJsonPath('data.situacao','aprovada');
        $this->postJson("/api/v1/catalogo/atendimento/$id/acoes",$d)->assertOk();
        $this->assertDatabaseCount('referencias_instituicoes_pessoais',0);$this->assertDatabaseCount('catalogo_solicitacao_eventos',3);
        foreach($antes as $t=>$json)$this->assertSame($json,DB::table($t)->get()->toJson(),$t);
    }
    public function test_nova_referencia_manual_selecionavel_sem_inep_inventado(): void {
        $id=$this->criar();$this->analisar($id);
        $d=['versao'=>2,'acao'=>'aprovar_nova','cadastro'=>$this->cadastro(),'situacao'=>'1','motivo'=>'Instituição e localização conferidas','conferencia'=>true];
        $r=$this->postJson("/api/v1/catalogo/atendimento/$id/acoes",$d)->assertOk()->json('data');
        $this->postJson("/api/v1/catalogo/atendimento/$id/acoes",$d)->assertOk();
        $this->entrar(1);$e=$this->getJson('/api/v1/catalogo/instituicoes?busca=Instituto')->assertOk()->assertJsonCount(1,'data.itens')->json('data.itens.0');
        $this->assertSame('manual',$e['fonte']);$this->assertSame('plataforma',$e['namespace']);$this->assertSame(26,strlen($e['codigo']));$this->assertStringNotContainsString('Pedido privado',json_encode($e));
        $this->postJson('/api/v1/catalogo/referencias-pessoais',['unidade_id'=>$r['unidade_id'],'edicao_id'=>$r['edicao_id'],'confirmacao'=>true])->assertOk();
        $this->getJson('/api/v1/catalogo/referencias-pessoais')->assertOk()->assertJsonPath('data.itens.0.edicao_corrente',true);
        $this->assertDatabaseCount('catalogo_manutencao_eventos',1);
    }
    public function test_revisao_oficial_tem_precedencia_sem_apagar_snapshot_pessoal(): void {
        $e=app(CatalogoSelecao::class)->buscar(['uf'=>'BA'])['itens'][0];$this->postJson('/api/v1/catalogo/referencias-pessoais',['unidade_id'=>$e['unidade_id'],'edicao_id'=>$e['edicao_id'],'confirmacao'=>true])->assertOk();
        $d=['cadastro'=>['nome'=>'Nome revisado manualmente','sigla'=>'NR','tipo'=>'escola','namespace'=>'inep_escola','codigo'=>$e['codigo'],'uf'=>'BA','municipio_ibge'=>'2900001','dependencia'=>'estadual','site'=>'https://example.test/evidencia'],'situacao'=>'1','motivo'=>'Correção documentada do nome','conferencia'=>true,'edicao_id'=>$e['edicao_id']];
        $this->entrar(3);$this->postJson('/api/v1/catalogo/atendimento/instituicoes/'.$e['unidade_id'],$d)->assertOk();
        $this->postJson('/api/v1/catalogo/atendimento/instituicoes/'.$e['unidade_id'],$d)->assertConflict();
        $this->entrar(1);$this->getJson('/api/v1/catalogo/instituicoes?uf=BA')->assertOk()->assertJsonCount(1,'data.itens')->assertJsonPath('data.itens.0.nome','Nome revisado manualmente');
        $this->getJson('/api/v1/catalogo/referencias-pessoais')->assertOk()->assertJsonPath('data.itens.0.instituicao.nome',$e['nome'])->assertJsonPath('data.itens.0.edicao_corrente',false);
        $this->postJson('/api/v1/catalogo/referencias-pessoais',['unidade_id'=>$e['unidade_id'],'edicao_id'=>$e['edicao_id'],'confirmacao'=>true])->assertConflict();
        $this->promover($e['edicao_id']);$this->assertSame('Nome revisado manualmente',app(CatalogoSelecao::class)->entrada($e['unidade_id'])->nome);
    }
    public function test_edicao_manual_fixada_entre_contagem_e_pagina_sem_ressuscitar_edicoes_antigas(): void {
        $consulta=app(\App\Services\ConsultarCatalogoInstituicoes::class);
        $base=$consulta->buscar(['uf'=>'BA'])->items()[0];
        $dados=['cadastro'=>['nome'=>'Primeira revisão manual','tipo'=>'escola','namespace'=>'inep_escola','codigo'=>$base->codigo,
            'uf'=>'BA','municipio_ibge'=>'2900001','dependencia'=>'estadual','site'=>'https://example.test/evidencia'],
            'situacao'=>'1','motivo'=>'Conferência fictícia de revisão','conferencia'=>true];
        $primeira=app(ManutencaoCatalogo::class)->gravar($this->ator(3),$dados,$base->unidade_id,$base->edicao_id);
        $trocou=false;$segunda=null;
        DB::listen(function($q)use(&$trocou,&$segunda,$dados,$base,$primeira){
            if(!$trocou && str_starts_with(strtolower($q->sql),'select') && str_contains($q->sql,'aggregate') && str_contains($q->sql,'catalogo_entradas')){
                $trocou=true;$dados['cadastro']['nome']='Segunda revisão manual';
                $segunda=app(ManutencaoCatalogo::class)->gravar($this->ator(3),$dados,$base->unidade_id,$primeira['edicao_id']);
            }
        });
        $pagina=$consulta->buscar(['uf'=>'BA']);
        $this->assertTrue($trocou);$this->assertSame(1,$pagina->total());
        $this->assertSame('Primeira revisão manual',$pagina->items()[0]->nome);
        $this->assertSame($primeira['edicao_id'],$pagina->items()[0]->edicao_id);
        $atual=$consulta->buscar(['uf'=>'BA']);$this->assertSame(1,$atual->total());
        $this->assertSame('Segunda revisão manual',$atual->items()[0]->nome);
        $this->assertSame($segunda['edicao_id'],$atual->items()[0]->edicao_id);
        $this->assertSame(3,DB::table('catalogo_entradas')->where('unidade_id',$base->unidade_id)->count());
    }
    public function test_falha_no_aviso_reverte_decisao_evento_e_cadastro_manual(): void {
        $id=$this->criar();$this->analisar($id);$antes=DB::table('catalogo_entradas')->count();$umaVez=false;
        DB::listen(function($q)use(&$umaVez){if(!$umaVez && str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'catalogo_solicitacao_avisos')){$umaVez=true;throw new \RuntimeException('Falha fictícia no aviso');}});
        try{app(SolicitacoesCatalogo::class)->alterar($this->ator(3),$id,['versao'=>2,'acao'=>'aprovar_nova','cadastro'=>$this->cadastro(),'situacao'=>'1','motivo'=>'Conferência fictícia completa','conferencia'=>true],true);$this->fail('Aviso obrigatório');}catch(\RuntimeException $e){$this->assertSame('Falha fictícia no aviso',$e->getMessage());}
        $this->assertDatabaseHas('catalogo_solicitacoes',['id'=>$id,'situacao'=>'em_analise','versao'=>2]);$this->assertSame($antes,DB::table('catalogo_entradas')->count());$this->assertDatabaseCount('catalogo_manutencao_eventos',0);$this->assertDatabaseCount('catalogo_solicitacao_eventos',2);
    }
    public function test_revogacao_retirada_de_permissao_payload_e_imutabilidade(): void {
        $id=$this->criar();
        $this->postJson('/api/v1/catalogo/minhas-solicitacoes',['chave'=>(string)Str::uuid(),'cadastro'=>$this->cadastro(),'usuario_id'=>2])->assertUnprocessable();
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'catalogo.solicitar','efeito'=>'retirar']);$this->getJson('/api/v1/catalogo/minhas-solicitacoes')->assertForbidden();
        $this->entrar(3);DB::table('usuario_acessos_internos')->where('id',3)->update(['situacao'=>'suspenso']);$this->getJson('/api/v1/catalogo/atendimento')->assertForbidden();
        foreach([fn()=>DB::table('catalogo_solicitacao_eventos')->update(['acao'=>'falso']),fn()=>DB::table('catalogo_solicitacao_avisos')->delete(),fn()=>DB::table('catalogo_solicitacoes')->where('id',$id)->update(['usuario_id'=>2])] as $acao){
            try{DB::transaction($acao);$this->fail('Histórico deve ser protegido');}catch(\Illuminate\Database\QueryException $e){$this->assertNotEmpty($e->getMessage());}
        }
    }
    public function test_codigo_conferido_converge_com_importador_sem_duplicar_unidade(): void {
        $d=$this->cadastro();$d['tipo']='escola';$d['namespace']='inep_escola';$d['codigo']='29999999';unset($d['observacao']);
        $r=app(ManutencaoCatalogo::class)->gravar($this->ator(3),['cadastro'=>$d,'situacao'=>'1','motivo'=>'Código conferido em fonte pública','conferencia'=>true]);
        $s=file_get_contents($this->fonte['arquivo']);$s=str_replace('29000001','29999999',$s);file_put_contents($this->fonte['arquivo'],$s);
        $anterior=DB::table('catalogo_fontes')->where('nome','basica')->value('publicacao_id');$this->promover($anterior);
        $this->assertDatabaseCount('catalogo_unidades',28);$this->assertSame($r['unidade_id'],app(CatalogoSelecao::class)->entrada($r['unidade_id'])->unidade_id);
        $this->assertCount(1,app(CatalogoSelecao::class)->buscar(['namespace'=>'inep_escola','codigo'=>'29999999'])['itens']);
    }
    public function test_gestao_exige_autoridade_comprovada_e_nao_recebe_protocolos(): void {
        DB::table('redes')->insert(['id'=>1,'nome'=>'Rede fictícia','tipo'=>'estadual','uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>1,'rede_id'=>1,'nome'=>'Núcleo fictício']);
        DB::table('clientes')->insert(['id'=>3,'tipo'=>'rede','nome'=>'Rede fictícia','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>3,'cliente_id'=>3,'tipo'=>'rede','rede_id'=>1,'nome'=>'Rede','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>4,'cliente_id'=>3,'tipo'=>'nucleo','nucleo_id'=>1,'nome'=>'Núcleo','situacao'=>'ativo']);
        foreach([6,7] as $id){
            Usuario::forceCreate(['id'=>$id,'nome'=>'Diretor fictício','perfil'=>'dir_escolar','email'=>"dir$id@example.test",'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
            DB::table('escolas')->insert(['id'=>$id,'nucleo_id'=>1,'nome'=>'Escola fictícia '.$id,'inep'=>'2900000'.($id-4),'tipo_rede'=>'estadual']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>3,'tipo'=>'escola','escola_id'=>$id,'nome'=>'Escola','situacao'=>'ativo']);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'dir_escolar','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        }
        $id=$this->criar();$e=app(CatalogoSelecao::class)->buscar(['uf'=>'BA'])['itens'][0];
        $diretor=Usuario::find(6)->noVinculo(6);Sanctum::actingAs($diretor);
        $this->getJson('/api/v1/catalogo/gestao')->assertOk()->assertJsonCount(0,'data.itens');$this->getJson('/api/v1/catalogo/gestao/instituicoes/'.$e['unidade_id'])->assertNotFound();
        $this->getJson('/api/v1/catalogo/atendimento')->assertForbidden();$this->getJson('/api/v1/catalogo/minhas-solicitacoes/'.$id)->assertForbidden();
        $d=['escola_id'=>6,'unidade_id'=>$e['unidade_id'],'versao'=>0,'situacao'=>'ativa','motivo'=>'Autoridade escolar conferida','site'=>'https://example.test/autoridade','conferencia'=>true];
        $this->entrar(3);$this->postJson('/api/v1/catalogo/atendimento/autoridades',$d)->assertOk()->assertJsonPath('data.versao',1);
        $this->getJson('/api/v1/catalogo/atendimento/opcoes?unidade_id='.$e['unidade_id'])->assertOk()->assertJsonCount(1,'data.autoridades')->assertJsonPath('data.autoridades.0.versao',1);
        Sanctum::actingAs($diretor);$this->getJson('/api/v1/catalogo/gestao')->assertOk()->assertJsonCount(1,'data.itens');
        $cad=['nome'=>'Nome de gestão conferido','tipo'=>'escola','namespace'=>'inep_escola','codigo'=>$e['codigo'],'uf'=>'BA','municipio_ibge'=>'2900001','dependencia'=>'estadual','site'=>'https://example.test/gestao'];
        $op=['edicao_id'=>$e['edicao_id'],'cadastro'=>$cad,'situacao'=>'1','motivo'=>'Correção autorizada de nome','conferencia'=>true];
        $this->postJson('/api/v1/catalogo/gestao/instituicoes/'.$e['unidade_id'],$op)->assertOk();
        Sanctum::actingAs(Usuario::find(7)->noVinculo(7));$this->postJson('/api/v1/catalogo/gestao/instituicoes/'.$e['unidade_id'],$op)->assertNotFound();
        $this->entrar(3);$d['versao']=1;$d['situacao']='revogada';$this->postJson('/api/v1/catalogo/atendimento/autoridades',$d)->assertOk();
        Sanctum::actingAs($diretor);$this->getJson('/api/v1/catalogo/gestao')->assertOk()->assertJsonCount(0,'data.itens');
        $this->assertDatabaseCount('catalogo_solicitacoes',1);$this->assertDatabaseCount('catalogo_manutencao_eventos',3);
    }
    public function test_limite_de_envio_conta_uma_vez_e_nao_duplica_efeitos(): void {
        $this->withMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $d=['chave'=>(string)Str::uuid(),'cadastro'=>$this->cadastro()];
        for($i=0;$i<5;$i++)$this->postJson('/api/v1/catalogo/minhas-solicitacoes',$d)->assertOk();
        $this->postJson('/api/v1/catalogo/minhas-solicitacoes',$d)->assertStatus(429);$this->assertDatabaseCount('catalogo_solicitacoes',1);
    }
    public function test_dependencia_desconhecida_e_confirmacao_para_encerrar(): void {
        $c=$this->cadastro();unset($c['dependencia']);
        $id=$this->postJson('/api/v1/catalogo/minhas-solicitacoes',['chave'=>(string)Str::uuid(),'cadastro'=>$c])->assertOk()->json('data.id');
        $this->getJson("/api/v1/catalogo/minhas-solicitacoes/$id")->assertOk()->assertJsonPath('data.dados.dependencia','nao_informada')->assertJsonPath('data.dados.municipio_nome','Município fictício BA');
        $this->postJson("/api/v1/catalogo/minhas-solicitacoes/$id/acoes",['acao'=>'cancelar','versao'=>1])->assertUnprocessable();
        $this->postJson("/api/v1/catalogo/minhas-solicitacoes/$id/acoes",['acao'=>'cancelar','versao'=>1,'conferencia'=>true])->assertOk()->assertJsonPath('data.situacao','cancelada');
        $id=$this->criar();$this->analisar($id);
        $this->postJson("/api/v1/catalogo/atendimento/$id/acoes",['acao'=>'indeferir','versao'=>2,'mensagem'=>'Instituição não identificada'])->assertUnprocessable();
        $this->postJson("/api/v1/catalogo/atendimento/$id/acoes",['acao'=>'indeferir','versao'=>2,'mensagem'=>'Instituição não identificada','conferencia'=>true])->assertOk()->assertJsonPath('data.situacao','indeferida');
    }
    public function test_revisar_referencia_manual_preserva_identidade_e_historico(): void {
        $this->entrar(3);$dados=['cadastro'=>$this->cadastro(),'motivo'=>'Conferência fictícia','situacao'=>'1','conferencia'=>true];
        $r=$this->postJson('/api/v1/catalogo/atendimento/instituicoes',$dados)->assertOk()->json('data');
        $e=app(CatalogoSelecao::class)->entrada($r['unidade_id']);
        $dados['edicao_id']=$r['edicao_id'];$dados['cadastro']['namespace']='plataforma';$dados['cadastro']['codigo']=$e->codigo;$dados['cadastro']['nome']='Instituto com nome revisado';
        $novo=$this->postJson('/api/v1/catalogo/atendimento/instituicoes/'.$r['unidade_id'],$dados)->assertOk()->json('data');
        $this->assertSame($r['unidade_id'],$novo['unidade_id']);$this->assertNotSame($r['edicao_id'],$novo['edicao_id']);
        $this->assertDatabaseCount('catalogo_manutencao_eventos',2);
    }
}
