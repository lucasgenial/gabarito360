<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Services\PedidosTitulares;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Crypt};
use Illuminate\Support\Str;
use Tests\TestCase;

class PedidosTitularesTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        config(['pedidos_titulares.ativo'=>true,'acessos_internos.ativo'=>true,'vinculos.obrigatorio'=>true]);
        foreach ([1=>'desenvolvedor',2=>'suporte',3=>'financeiro',4=>'suporte'] as $id=>$perfil) {
            Usuario::forceCreate(['id'=>$id,'perfil'=>'admin_rede','nome'=>'Fictício','email'=>"l4-$id@example.test",
                'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
            DB::table('usuario_acessos_internos')->insert(['id'=>$id,'usuario_id'=>$id,'perfil'=>$perfil,
                'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
        foreach ([1,2] as $id) {
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Cliente fictício','modalidade'=>'teste','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>'Espaço fictício','situacao'=>'ativo','usuario_proprietario_id'=>$id]);
        }
    }
    private function ator(int $id=1): Usuario { return Usuario::find($id)->noAcessoInterno($id,1); }
    private function dados(int $espaco=1,int $responsavel=2): array
    {
        return ['cliente_id'=>$espaco,'espaco_id'=>$espaco,'responsavel_acesso_id'=>$responsavel,'tipo'=>'acesso',
            'identificacao_declarada'=>'Titular fictício não verificado','relato'=>'Relato reservado de teste',
            'motivo'=>'Atendimento institucional fictício','chave'=>(string)Str::uuid()];
    }
    private function chamar(string $token,string $method,string $url,array $dados=[])
    {
        app('auth')->forgetGuards();
        return $this->json($method,'/api/v1/'.$url,$dados,['Authorization'=>'Bearer '.$token]);
    }
    private function token(int $id): string
    {
        $t=Usuario::find($id)->createToken('api-token')->plainTextToken;
        return $this->chamar($t,'POST','auth/acessos-internos/selecionar',['acesso_interno_id'=>$id])->assertCreated()->json('data.token');
    }
    public function test_abertura_cifra_conteudo_e_recibo_nao_da_acesso_ao_criador(): void
    {
        $dev=$this->token(1);$suporte=$this->token(2);$financeiro=$this->token(3);
        $dados=$this->dados();$r=$this->chamar($dev,'POST','pedidos-titulares',$dados)->assertCreated();$id=$r->json('data.referencia');
        $r->assertJsonMissing(['relato'=>$dados['relato']]);
        $p=DB::table('pedidos_titulares')->where('id',$id)->first();
        $this->assertNotSame($dados['relato'],$p->relato);
        $this->assertSame($dados['relato'],Crypt::decryptString($p->relato));
        $this->assertSame($dados['identificacao_declarada'],Crypt::decryptString($p->identificacao_declarada));
        $this->chamar($dev,'GET','pedidos-titulares/'.$id)->assertNotFound();
        $this->chamar($financeiro,'GET','pedidos-titulares')->assertForbidden();
        $this->chamar($financeiro,'GET','pedidos-titulares/'.$id)->assertForbidden();
        $this->chamar($financeiro,'POST','pedidos-titulares',$dados)->assertForbidden();
        $this->chamar($suporte,'POST','pedidos-titulares',$dados)->assertForbidden();
        $this->chamar($suporte,'GET','pedidos-titulares/'.$id)->assertOk()->assertJsonPath('data.relato',$dados['relato']);
        $this->assertDatabaseCount('pedido_titular_eventos',2);
        $this->assertStringNotContainsString($dados['motivo'],DB::table('pedido_titular_eventos')->first()->motivo);
    }
    public function test_isola_lista_detalhe_e_triagem_entre_dois_responsaveis_e_instituicoes(): void
    {
        $s=app(PedidosTitulares::class);$a=$s->abrir($this->ator(),$this->dados());$b=$s->abrir($this->ator(),$this->dados(2,4));
        $t=$this->token(2);$outro=$this->token(4);
        $this->chamar($t,'GET','pedidos-titulares')->assertOk()->assertJsonPath('data.total',1)->assertJsonPath('data.data.0.referencia',$a['referencia']);
        $this->chamar($outro,'GET','pedidos-titulares')->assertOk()->assertJsonPath('data.total',1)->assertJsonPath('data.data.0.referencia',$b['referencia']);
        $this->chamar($t,'GET','pedidos-titulares/'.$b['referencia'])->assertNotFound();
        $this->chamar($t,'POST','pedidos-titulares/'.$b['referencia'].'/triagem',$this->triagem())->assertNotFound();
        $this->chamar($t,'GET','pedidos-titulares')->assertJsonMissing(['identificacao_declarada'=>'Titular fictício não verificado']);
    }
    private function triagem(): array
    {
        return ['situacao'=>'em_verificacao','versao'=>1,'motivo'=>'Iniciando conferência fictícia','chave'=>(string)Str::uuid()];
    }
    public function test_idempotencia_e_conflitos_na_abertura_e_na_triagem(): void
    {
        $s=app(PedidosTitulares::class);$dados=$this->dados();$p=$s->abrir($this->ator(),$dados);
        $this->assertSame($p,$s->abrir($this->ator(),$dados));$this->assertDatabaseCount('pedidos_titulares',1);
        $dev=$this->token(1);$t=$this->token(2);$url='pedidos-titulares/'.$p['referencia'].'/triagem';
        $this->chamar($dev,'POST','pedidos-titulares',array_replace($dados,['relato'=>'Diferente']))->assertConflict();
        $v=$this->triagem();$r=$this->chamar($t,'POST',$url,$v)->assertOk()->assertJsonPath('data.versao',2)->json('data');
        $this->assertSame($r,$this->chamar($t,'POST',$url,$v)->assertOk()->json('data'));
        $this->chamar($t,'POST',$url,array_replace($v,['motivo'=>'Diferente']))->assertConflict();
        $this->chamar($t,'POST',$url,array_replace($v,['chave'=>(string)Str::uuid(),'situacao'=>'aguardando_informacao']))->assertConflict();
        $this->assertDatabaseCount('pedido_titular_eventos',2);$this->assertDatabaseCount('pedido_titular_recibos',2);
    }
    public function test_nao_aceita_escopo_incoerente_flags_de_verificacao_ou_atalho_para_analise(): void
    {
        $dev=$this->token(1);$t=$this->token(2);$dados=$this->dados();
        $this->chamar($dev,'POST','pedidos-titulares',array_replace($dados,['cliente_id'=>2]))->assertNotFound();
        $this->chamar($dev,'POST','pedidos-titulares',array_replace($dados,['responsavel_acesso_id'=>3]))->assertNotFound();
        $this->chamar($dev,'POST','pedidos-titulares',array_replace($dados,['situacao'=>'concluida']))->assertUnprocessable();
        $id=$this->chamar($dev,'POST','pedidos-titulares',$dados)->assertCreated()->json('data.referencia');
        $url='pedidos-titulares/'.$id.'/triagem';
        foreach (['em_analise','concluida'] as $estado) $this->chamar($t,'POST',$url,array_replace($this->triagem(),['situacao'=>$estado]))->assertUnprocessable();
        $this->chamar($t,'POST',$url,$this->triagem()+['identidade_verificada'=>true])->assertUnprocessable();
        $this->chamar($t,'POST',$url,array_replace($this->triagem(),['situacao'=>'cancelada']))->assertUnprocessable();
        $this->chamar($t,'POST',$url,array_replace($this->triagem(),['situacao'=>'cancelada','resultado'=>'inviavel_verificar']))->assertOk()->assertJsonPath('data.situacao','cancelada');
        $this->chamar($t,'POST',$url,array_replace($this->triagem(),['versao'=>2]))->assertUnprocessable();
    }
    public function test_revogacao_versao_expiracao_flag_e_identidade_sem_contexto(): void
    {
        $s=app(PedidosTitulares::class);$d=$this->dados();$d['atribuicao_fim_em']=now()->addHour()->toIso8601String();$p=$s->abrir($this->ator(),$d);
        $t=$this->token(2);$id=$p['referencia'];
        $identidade=Usuario::find(2)->createToken('api-token')->plainTextToken;
        $this->chamar($identidade,'GET','pedidos-titulares')->assertForbidden();
        $this->travel(2)->hours();$this->chamar($t,'GET','pedidos-titulares/'.$id)->assertNotFound();$this->travelBack();
        DB::table('usuario_acessos_internos')->where('id',2)->update(['versao_autorizacao'=>2]);
        $this->chamar($t,'GET','pedidos-titulares')->assertForbidden();
        $novo=$this->token(2);$this->chamar($novo,'GET','pedidos-titulares/'.$id)->assertNotFound();
        $this->chamar($novo,'GET','pedidos-titulares')->assertJsonPath('data.total',0);
        config(['pedidos_titulares.ativo'=>false]);$this->chamar($novo,'GET','pedidos-titulares')->assertForbidden();
    }
    public function test_falha_no_historico_desfaz_protocolo_e_recibo(): void
    {
        DB::listen(function ($q) {
            if (str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'pedido_titular_eventos')) throw new \RuntimeException('Falha fictícia de histórico');
        });
        try { app(PedidosTitulares::class)->abrir($this->ator(),$this->dados());$this->fail('Deveria falhar'); }
        catch (\RuntimeException $e) { $this->assertSame('Falha fictícia de histórico',$e->getMessage()); }
        $this->assertDatabaseCount('pedidos_titulares',0);$this->assertDatabaseCount('pedido_titular_recibos',0);
    }

    public function test_escopo_inativo_e_acesso_suspenso_nao_revelam_protocolos(): void
    {
        $p=app(PedidosTitulares::class)->abrir($this->ator(),$this->dados());$t=$this->token(2);
        DB::table('clientes')->where('id',1)->update(['situacao'=>'suspenso']);
        $this->chamar($t,'GET','pedidos-titulares')->assertJsonPath('data.total',0);
        $this->chamar($t,'GET','pedidos-titulares/'.$p['referencia'])->assertNotFound();
        DB::table('clientes')->where('id',1)->update(['situacao'=>'ativo']);
        DB::table('usuario_acessos_internos')->where('id',2)->update(['situacao'=>'suspenso']);
        $this->chamar($t,'GET','pedidos-titulares')->assertForbidden();
    }

    public function test_falha_no_historico_desfaz_tambem_a_triagem(): void
    {
        $s=app(PedidosTitulares::class);$p=$s->abrir($this->ator(),$this->dados());
        DB::listen(function ($q) {
            if (str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'pedido_titular_eventos')) throw new \RuntimeException('Falha fictícia de histórico');
        });
        try { $s->triar($this->ator(2),$p['referencia'],$this->triagem());$this->fail('Deveria falhar'); }
        catch (\RuntimeException $e) { $this->assertSame('Falha fictícia de histórico',$e->getMessage()); }
        $this->assertDatabaseHas('pedidos_titulares',['id'=>$p['referencia'],'situacao'=>'recebida','versao'=>1]);
        $this->assertDatabaseCount('pedido_titular_recibos',1);
    }

    public function test_mesma_identidade_em_dois_acessos_nao_mistura_atribuicoes(): void
    {
        DB::table('usuario_acessos_internos')->insert(['id'=>5,'usuario_id'=>1,'perfil'=>'suporte',
            'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        $s=app(PedidosTitulares::class);
        $a=$s->abrir($this->ator(),$this->dados(1,1));
        $b=$s->abrir($this->ator(),$this->dados(2,5));
        $this->assertSame($a['referencia'],$s->listar($this->ator())->items()[0]->referencia);
        $outro=Usuario::find(1)->noAcessoInterno(5,1);
        $this->assertSame($b['referencia'],$s->listar($outro)->items()[0]->referencia);
        try { $s->consultar($outro,$a['referencia']);$this->fail('Acesso deveria ser recusado'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(404,$e->getStatusCode()); }
    }
}
