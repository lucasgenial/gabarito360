<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\{CatalogoSelecao,PrepararCatalogoInstituicoes,PublicarCatalogoInstituicoes,ReferenciasInstituicoesPessoais};
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\ArquivosCatalogo;
use Tests\TestCase;

class CatalogoPessoalTest extends TestCase
{
    use RefreshDatabase,ArquivosCatalogo;
    private array $fonte;
    protected function setUp(): void
    {
        parent::setUp();config(['catalogo.interface_ativa'=>true,'espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true]);Permissao::limparCache();
        foreach([1,2] as $id) {
            Usuario::forceCreate(['id'=>$id,'nome'=>'Professor fictício','perfil'=>'professor','email'=>"cat$id@example.test",'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Cliente fictício','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>"Pessoal $id",'situacao'=>'ativo','usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        }
        $this->entrar();
    }
    protected function tearDown(): void { $this->limparArquivosCatalogo();parent::tearDown(); }
    private function entrar(int $id=1): void { Sanctum::actingAs(Usuario::find($id)->noVinculo($id)); }
    private function publicar(string $fonte='basica'): void {
        $this->fonte=$this->fonteCatalogo($fonte);$this->promover();
    }
    private function promover(?string $anterior=null): void {
        $this->fonte['sha256']=hash_file('sha256',$this->fonte['arquivo']);
        $r=app(PrepararCatalogoInstituicoes::class)->executar(...$this->fonte);
        app(PublicarCatalogoInstituicoes::class)->executar($r['importacao_id'],$this->fonte['ufs'],substituir:$anterior,motivo:$anterior?'Revisão fictícia de teste':null);
    }
    private function escolha(array $f=['uf'=>'RO']): array {
        $e=app(CatalogoSelecao::class)->buscar($f)['itens'][0];return ['unidade_id'=>$e['unidade_id'],'edicao_id'=>$e['edicao_id'],'confirmacao'=>true];
    }
    public function test_consulta_minima_filtros_geografia_e_codigo_sem_escola_criar(): void {
        $this->publicar();$this->publicar('superior');
        $this->getJson('/api/v1/catalogo/ufs')->assertOk()->assertJsonCount(27,'data.itens');
        $this->getJson('/api/v1/catalogo/municipios?uf=BA&busca=munic%C3%ADpio')->assertOk()->assertJsonPath('data.itens.0.codigo_ibge','2900001');
        $r=$this->getJson('/api/v1/catalogo/instituicoes?uf=BA&tipo=escola')->assertOk()->assertJsonCount(1,'data.itens')->assertJsonPath('data.itens.0.codigo','29000001')->json('data.itens.0');
        $this->assertSame(['unidade_id','instituicao_id','nome','sigla','namespace','codigo','tipo','natureza','dependencia','situacao_fonte','uf','municipio_ibge','municipio_nome','edicao_id','ano','fonte','elegivel','situacao_rotulo'],array_keys($r));
        $this->getJson('/api/v1/catalogo/instituicoes?namespace=inep_escola&codigo=29000001')->assertOk()->assertJsonCount(1,'data.itens');
        $this->getJson('/api/v1/catalogo/instituicoes?busca=IESBA')->assertOk()->assertJsonPath('data.itens.0.namespace','inep_ies')->assertJsonPath('data.itens.0.situacao_rotulo','Situação não informada na fonte')->assertJsonPath('data.itens.0.elegivel',true);
        $this->getJson('/api/v1/catalogo/instituicoes?uf=BA&municipio_ibge=1100001')->assertUnprocessable();
        $this->getJson('/api/v1/catalogo/instituicoes?busca=aa')->assertUnprocessable();
        $this->getJson('/api/v1/catalogo/instituicoes?uf=BA&per_page=51')->assertUnprocessable();
        $this->getJson('/api/v1/catalogo/instituicoes?busca=%25%25%25')->assertOk()->assertJsonCount(0,'data.itens');
        $this->getJson('/api/v1/catalogo/instituicoes?uf=BA&tipo=universidade')->assertOk();
        $this->getJson('/api/v1/catalogo/instituicoes?busca=escola&per_page=10&page=2')->assertOk()->assertJsonCount(10,'data.itens')->assertJsonPath('data.paginacao.total',27)->assertJsonPath('data.paginacao.atual',2);
        $this->getJson('/api/v1/escolas')->assertForbidden();
    }
    public function test_sem_edicao_retorna_indisponivel_mas_lista_pessoal_continua_vazia(): void {
        $this->getJson('/api/v1/catalogo/instituicoes?uf=BA')->assertStatus(503);
        $this->getJson('/api/v1/catalogo/referencias-pessoais')->assertOk()->assertJsonCount(0,'data.itens');
        config(['catalogo.interface_ativa'=>false]);$this->getJson('/api/v1/catalogo/ufs')->assertStatus(503);
    }
    public function test_selecao_isolada_idempotente_e_arquivamento_preservam_dados(): void {
        $this->publicar();
        DB::table('provas')->insert(['id'=>1,'espaco_id'=>1,'espaco_tipo'=>'pessoal','criado_por'=>1,'titulo'=>'Preservada','disciplina'=>'Teste','serie'=>'1','num_questoes'=>1,'num_alternativas'=>5]);
        DB::table('gabaritos')->insert(['id'=>1,'prova_id'=>1,'espaco_id'=>1]);
        DB::table('gabarito_questoes')->insert(['gabarito_id'=>1,'espaco_id'=>1,'numero_questao'=>1,'alternativa'=>'B','peso'=>2]);
        $antes=[];foreach(['escolas','espacos','usuarios','usuario_vinculos','turmas','alunos','provas','gabaritos','gabarito_questoes','cartoes','notas'] as $t) $antes[$t]=DB::table($t)->get()->toJson();
        $d=$this->escolha();$id=$this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertOk()->json('data.id');
        $this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertOk()->assertJsonPath('data.id',$id)->assertJsonPath('data.versao',1);
        $this->assertDatabaseCount('catalogo_referencia_eventos',1);
        $this->getJson('/api/v1/catalogo/referencias-pessoais')->assertOk()->assertJsonPath('data.itens.0.edicao_corrente',true);
        $this->entrar(2);$this->getJson('/api/v1/catalogo/referencias-pessoais')->assertOk()->assertJsonCount(0,'data.itens');
        $this->postJson('/api/v1/catalogo/referencias-pessoais/'.$id.'/arquivar',['versao'=>1])->assertNotFound();
        $id2=$this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertOk()->json('data.id');$this->assertNotSame($id,$id2);
        $this->entrar();
        $this->postJson('/api/v1/catalogo/referencias-pessoais/'.$id.'/arquivar',['versao'=>99])->assertConflict();
        $this->postJson('/api/v1/catalogo/referencias-pessoais/'.$id.'/arquivar',['versao'=>1])->assertOk()->assertJsonPath('data.versao',2);
        $this->postJson('/api/v1/catalogo/referencias-pessoais/'.$id.'/arquivar',['versao'=>1])->assertOk()->assertJsonPath('data.versao',2);
        $this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertOk()->assertJsonPath('data.id',$id)->assertJsonPath('data.versao',3);
        $this->assertDatabaseCount('referencias_instituicoes_pessoais',2);$this->assertDatabaseCount('catalogo_referencia_eventos',4);
        foreach($antes as $t=>$json) $this->assertSame($json,DB::table($t)->get()->toJson(),$t);
    }
    public function test_edicao_obsoleta_e_situacao_extinta_nao_sao_confirmadas(): void {
        $this->publicar();$d=$this->escolha();$this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertOk();
        $s=file_get_contents($this->fonte['arquivo']);$s=str_replace('Escola fictícia RO;RO;11;1100001;Município fictício RO;2;1','Nome revisado RO;RO;11;1100001;Município fictício RO;2;3',$s);file_put_contents($this->fonte['arquivo'],$s);
        $this->promover($d['edicao_id']);
        $this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertConflict();
        $this->getJson('/api/v1/catalogo/referencias-pessoais')->assertOk()->assertJsonPath('data.itens.0.instituicao.nome','Escola fictícia RO')->assertJsonPath('data.itens.0.edicao_corrente',false)->assertJsonPath('data.itens.0.elegivel_atualmente',false);
        $novo=$this->escolha(['uf'=>'RO','situacao'=>'todas']);$this->postJson('/api/v1/catalogo/referencias-pessoais',$novo)->assertUnprocessable();
        $this->assertDatabaseCount('catalogo_referencia_eventos',1);
    }
    public function test_ator_forjado_confirmacao_ausente_e_permissao_retirada_recusados(): void {
        $this->publicar();$d=$this->escolha();
        $this->postJson('/api/v1/catalogo/referencias-pessoais',$d+['espaco_id'=>2])->assertUnprocessable();
        $d['confirmacao']=false;$this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertUnprocessable();
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'catalogo.referencia_pessoal.gerir','efeito'=>'retirar']);
        $d['confirmacao']=true;$this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertForbidden();
        $this->getJson('/api/v1/catalogo/ufs')->assertOk();
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);$this->getJson('/api/v1/catalogo/ufs')->assertForbidden();
        $this->assertDatabaseCount('referencias_instituicoes_pessoais',0);
    }
    public function test_erro_do_evento_reverte_toda_referencia(): void {
        $this->publicar();$d=$this->escolha();$umaVez=false;
        DB::listen(function($q)use(&$umaVez){if(!$umaVez && str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'catalogo_referencia_eventos')){$umaVez=true;throw new \RuntimeException('Falha fictícia no evento');}});
        try{app(ReferenciasInstituicoesPessoais::class)->selecionar(Usuario::find(1)->noVinculo(1),$d['unidade_id'],$d['edicao_id']);$this->fail('Evento obrigatório');}catch(\RuntimeException $e){$this->assertSame('Falha fictícia no evento',$e->getMessage());}
        $this->assertDatabaseCount('referencias_instituicoes_pessoais',0);$this->assertDatabaseCount('catalogo_referencia_eventos',0);
    }
    public function test_banco_protege_proprietario_identidade_e_eventos(): void {
        $this->publicar();$d=$this->escolha();$id=$this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertOk()->json('data.id');
        foreach([fn()=>DB::table('catalogo_referencia_eventos')->update(['acao'=>'alterada']),fn()=>DB::table('catalogo_referencia_eventos')->delete(),fn()=>DB::table('referencias_instituicoes_pessoais')->where('id',$id)->update(['usuario_id'=>2]),fn()=>DB::table('referencias_instituicoes_pessoais')->delete()] as $acao) {
            try{DB::transaction($acao);$this->fail('Banco deve recusar');}catch(\Illuminate\Database\QueryException $e){$this->assertNotEmpty($e->getMessage());}
        }
        $c=(array)DB::table('referencias_instituicoes_pessoais')->first();$c['id']=(string)\Illuminate\Support\Str::uuid();$c['usuario_id']=2;
        $outra=DB::table('catalogo_entradas')->where('publicacao_id',$c['publicacao_id'])->where('unidade_id','<>',$c['unidade_id'])->first();
        $c['unidade_id']=$outra->unidade_id;$c['entrada_id']=$outra->id;
        try{DB::transaction(fn()=>DB::table('referencias_instituicoes_pessoais')->insert($c));$this->fail('FK do proprietário');}catch(\Illuminate\Database\QueryException $e){$this->assertNotEmpty($e->getCode());}
    }
    public function test_edicao_nova_corrente_exige_reconfirmar_preservando_eventos(): void {
        $this->publicar();$d=$this->escolha();$id=$this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertOk()->json('data.id');
        file_put_contents($this->fonte['arquivo'],str_replace('Escola fictícia RO','Escola revisada RO',file_get_contents($this->fonte['arquivo'])));$this->promover($d['edicao_id']);
        $novo=$this->escolha();$this->postJson('/api/v1/catalogo/referencias-pessoais',$novo)->assertOk()->assertJsonPath('data.id',$id)->assertJsonPath('data.versao',2);
        $this->getJson('/api/v1/catalogo/referencias-pessoais')->assertOk()->assertJsonPath('data.itens.0.instituicao.nome','Escola revisada RO')->assertJsonPath('data.itens.0.edicao_corrente',true);
        $this->assertDatabaseHas('catalogo_referencia_eventos',['referencia_id'=>$id,'acao'=>'reconfirmada','anterior_id'=>$d['edicao_id'],'publicacao_id'=>$novo['edicao_id']]);
    }
    public function test_revogacao_intercalada_antes_do_commit_recusa_e_reverte_referencia(): void {
        $this->publicar();$d=$this->escolha();$revogado=false;
        DB::listen(function($q)use(&$revogado){if(!$revogado && str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'referencias_instituicoes_pessoais')){$revogado=true;DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);}});
        $this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertForbidden();
        $this->assertDatabaseCount('referencias_instituicoes_pessoais',0);$this->assertDatabaseCount('catalogo_referencia_eventos',0);
    }
    public function test_uuid_maiusculo_e_canonico_geram_a_mesma_referencia_corrente(): void {
        $this->publicar();$d=$this->escolha();$maiusculo=$d;$maiusculo['unidade_id']=strtoupper($d['unidade_id']);$maiusculo['edicao_id']=strtoupper($d['edicao_id']);
        $id=$this->postJson('/api/v1/catalogo/referencias-pessoais',$maiusculo)->assertOk()->json('data.id');
        $this->postJson('/api/v1/catalogo/referencias-pessoais',$d)->assertOk()->assertJsonPath('data.id',$id);
        $this->getJson('/api/v1/catalogo/instituicoes/'.strtoupper($d['unidade_id']))->assertOk()->assertJsonPath('data.unidade_id',$d['unidade_id']);
        $this->getJson('/api/v1/catalogo/referencias-pessoais')->assertOk()->assertJsonPath('data.itens.0.edicao_corrente',true);
        $this->assertDatabaseHas('referencias_instituicoes_pessoais',['id'=>$id,'unidade_id'=>$d['unidade_id'],'publicacao_id'=>$d['edicao_id']]);
        $this->postJson('/api/v1/catalogo/referencias-pessoais/'.strtoupper($id).'/arquivar',['versao'=>1])->assertOk();
        $this->assertDatabaseCount('referencias_instituicoes_pessoais',1);$this->assertDatabaseCount('catalogo_referencia_eventos',2);
    }
}
