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

    private function emVerificacao(): array
    {
        $s=app(PedidosTitulares::class);$p=$s->abrir($this->ator(),$this->dados());
        return $s->triar($this->ator(2),$p['referencia'],$this->triagem());
    }
    private function conferencia(string $tipo,int $versao): array
    {
        return ['tipo'=>$tipo,'metodo'=>$tipo==='identidade'?'conferencia_documental':'confirmacao_institucional',
            'evidencia_referencia'=>'Referência reservada fictícia '.$tipo,'valida_ate'=>now()->addHour()->toIso8601String(),
            'versao'=>$versao,'motivo'=>'Conferência manual fictícia','chave'=>(string)Str::uuid()];
    }
    private function comConferencias(): array
    {
        $s=app(PedidosTitulares::class);$p=$this->emVerificacao();
        foreach (['identidade','autoridade'] as $tipo) $p=$s->verificar($this->ator(2),$p['referencia'],$this->conferencia($tipo,$p['versao']));
        return $p;
    }
    private function decisao(int $versao,bool $concluir=false): array
    {
        $v=['situacao'=>$concluir?'concluida':'em_analise','versao'=>$versao,'motivo'=>'Decisão administrativa fictícia','chave'=>(string)Str::uuid()];
        return $concluir ? $v+['resultado'=>'indeferido','resposta_referencia'=>'Resposta administrativa fictícia'] : $v;
    }
    public function test_api_verifica_analisa_e_conclui_sem_operar_dados_escolares(): void
    {
        $p=$this->emVerificacao();$t=$this->token(2);$base='pedidos-titulares/'.$p['referencia'];
        foreach (['identidade','autoridade'] as $tipo) {
            $v=$this->conferencia($tipo,$p['versao']);
            $p=$this->chamar($t,'POST',$base.'/verificacoes',$v)->assertOk()->json('data');
        }
        $this->chamar($t,'GET',$base)->assertOk()->assertJsonCount(2,'data.verificacoes')->assertJsonPath('data.ciclo_verificacao',1);
        $p=$this->chamar($t,'POST',$base.'/decisao',$this->decisao($p['versao']))->assertOk()->assertJsonPath('data.situacao','em_analise')->json('data');
        $final=$this->decisao($p['versao'],true);
        $r=$this->chamar($t,'POST',$base.'/decisao',$final)->assertOk()->assertJsonPath('data.situacao','concluida')->json('data');
        $this->assertSame($r,$this->chamar($t,'POST',$base.'/decisao',$final)->assertOk()->json('data'));
        $this->chamar($t,'POST',$base.'/decisao',$this->decisao($r['versao']))->assertUnprocessable();
        $row=DB::table('pedidos_titulares')->where('id',$r['referencia'])->first();
        $this->assertSame($final['resposta_referencia'],Crypt::decryptString($row->resposta_referencia));
        $this->assertNotNull($row->concluido_em);
        $e=DB::table('pedido_titular_verificacoes')->first();
        $this->assertStringStartsWith('Referência reservada',Crypt::decryptString($e->evidencia_referencia));
        $this->assertSame(2,(int)$e->ator_id);$this->assertSame(1,(int)$e->ciclo);
        $this->assertDatabaseCount('gabaritos',0);$this->assertDatabaseCount('pedido_titular_verificacoes',2);
    }
    public function test_faltas_de_evidencia_flags_fabricadas_metodo_errado_e_versao_obsoleta(): void
    {
        $p=$this->emVerificacao();$t=$this->token(2);$base='pedidos-titulares/'.$p['referencia'];
        $this->chamar($t,'POST',$base.'/decisao',$this->decisao(2))->assertUnprocessable();
        $this->chamar($t,'POST',$base.'/decisao',$this->decisao(2)+['identidade_verificada'=>true,'autoridade_verificada'=>true])->assertUnprocessable();
        $v=$this->conferencia('identidade',2);
        $this->chamar($t,'POST',$base.'/verificacoes',array_replace($v,['metodo'=>'confirmacao_institucional']))->assertUnprocessable();
        $this->chamar($t,'POST',$base.'/verificacoes',$v+['ator_id'=>1])->assertUnprocessable();
        $this->chamar($t,'POST',$base.'/verificacoes',$v)->assertOk();
        $this->chamar($t,'POST',$base.'/decisao',$this->decisao(3))->assertUnprocessable();
        $this->chamar($t,'POST',$base.'/verificacoes',$this->conferencia('autoridade',2))->assertConflict();
        $this->assertDatabaseCount('pedido_titular_verificacoes',1);
    }
    public function test_idempotencia_nao_sobrescreve_ou_renova_evidencia(): void
    {
        $p=$this->emVerificacao();$t=$this->token(2);$url='pedidos-titulares/'.$p['referencia'].'/verificacoes';
        $v=$this->conferencia('identidade',2);$r=$this->chamar($t,'POST',$url,$v)->assertOk()->json('data');
        $this->assertSame($r,$this->chamar($t,'POST',$url,$v)->assertOk()->json('data'));
        $this->chamar($t,'POST',$url,array_replace($v,['evidencia_referencia'=>'Outra referência']))->assertConflict();
        $this->chamar($t,'POST',$url,$this->conferencia('identidade',3))->assertConflict();
        $this->travel(2)->hours();
        $this->assertSame($r,$this->chamar($t,'POST',$url,$v)->assertOk()->json('data'));
        $this->chamar($t,'GET','pedidos-titulares/'.$p['referencia'])->assertOk()->assertJsonPath('data.verificacoes.0.vigente',false);
        $this->travelBack();$this->assertDatabaseCount('pedido_titular_verificacoes',1);
    }
    public function test_expiracao_e_novo_ciclo_invalidam_as_duas_conferencias(): void
    {
        $s=app(PedidosTitulares::class);$p=$this->comConferencias();$t=$this->token(2);$base='pedidos-titulares/'.$p['referencia'];
        $p=$s->decidir($this->ator(2),$p['referencia'],$this->decisao($p['versao']));
        $this->travel(2)->hours();
        $this->chamar($t,'POST',$base.'/decisao',$this->decisao($p['versao'],true))->assertUnprocessable();
        $this->travelBack();
        $p=$s->triar($this->ator(2),$p['referencia'],array_replace($this->triagem(),['situacao'=>'aguardando_informacao','versao'=>$p['versao']]));
        $p=$s->triar($this->ator(2),$p['referencia'],array_replace($this->triagem(),['versao'=>$p['versao']]));
        $this->chamar($t,'GET',$base)->assertOk()->assertJsonPath('data.ciclo_verificacao',2)->assertJsonCount(0,'data.verificacoes');
        $this->chamar($t,'POST',$base.'/decisao',$this->decisao($p['versao']))->assertUnprocessable();
        foreach (['identidade','autoridade'] as $tipo) $p=$s->verificar($this->ator(2),$p['referencia'],$this->conferencia($tipo,$p['versao']));
        $this->chamar($t,'POST',$base.'/decisao',$this->decisao($p['versao']))->assertOk();
        $this->assertDatabaseCount('pedido_titular_verificacoes',4);
    }
    public function test_novas_rotas_preservam_atribuicao_e_revogacao(): void
    {
        $p=$this->comConferencias();$base='pedidos-titulares/'.$p['referencia'];
        foreach ([1=>404,3=>403,4=>404] as $id=>$status) {
            $t=$this->token($id);
            $this->chamar($t,'POST',$base.'/verificacoes',$this->conferencia('identidade',4))->assertStatus($status);
            $this->chamar($t,'POST',$base.'/decisao',$this->decisao(4))->assertStatus($status);
        }
        $t=$this->token(2);
        DB::table('usuario_acessos_internos')->where('id',2)->update(['versao_autorizacao'=>2]);
        $this->chamar($t,'POST',$base.'/decisao',$this->decisao(4))->assertForbidden();
        $this->chamar($this->token(2),'POST',$base.'/decisao',$this->decisao(4))->assertNotFound();
    }
    public function test_conclusao_exige_resultado_e_referencia_de_resposta(): void
    {
        $s=app(PedidosTitulares::class);$p=$this->comConferencias();$t=$this->token(2);$url='pedidos-titulares/'.$p['referencia'].'/decisao';
        $this->chamar($t,'POST',$url,$this->decisao(4)+['resposta_referencia'=>'Resposta antecipada'])->assertUnprocessable();
        $p=$s->decidir($this->ator(2),$p['referencia'],$this->decisao(4));
        $v=$this->decisao($p['versao'],true);
        foreach (['resultado','resposta_referencia'] as $campo) {
            $incompleto=$v;unset($incompleto[$campo]);
            $this->chamar($t,'POST',$url,$incompleto)->assertUnprocessable();
        }
        $this->chamar($t,'POST',$url,array_replace($v,['resultado'=>'cancelado_pelo_solicitante']))->assertUnprocessable();
        $this->assertDatabaseHas('pedidos_titulares',['id'=>$p['referencia'],'situacao'=>'em_analise','resposta_referencia'=>null]);
    }
    public function test_historico_falhando_desfaz_evidencia_versao_e_recibo(): void
    {
        $p=$this->emVerificacao();
        DB::listen(function ($q) {
            if (str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'pedido_titular_eventos')) throw new \RuntimeException('Falha fictícia de histórico');
        });
        try { app(PedidosTitulares::class)->verificar($this->ator(2),$p['referencia'],$this->conferencia('identidade',2));$this->fail('Deveria falhar'); }
        catch (\RuntimeException $e) { $this->assertSame('Falha fictícia de histórico',$e->getMessage()); }
        $this->assertDatabaseCount('pedido_titular_verificacoes',0);
        $this->assertDatabaseHas('pedidos_titulares',['id'=>$p['referencia'],'versao'=>2]);
        $this->assertDatabaseCount('pedido_titular_recibos',2);
    }
    public function test_historico_falhando_desfaz_conclusao_e_resposta(): void
    {
        $s=app(PedidosTitulares::class);$p=$this->comConferencias();$p=$s->decidir($this->ator(2),$p['referencia'],$this->decisao(4));
        DB::listen(function ($q) {
            if (str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'pedido_titular_eventos')) throw new \RuntimeException('Falha fictícia de histórico');
        });
        try { $s->decidir($this->ator(2),$p['referencia'],$this->decisao(5,true));$this->fail('Deveria falhar'); }
        catch (\RuntimeException $e) { $this->assertSame('Falha fictícia de histórico',$e->getMessage()); }
        $this->assertDatabaseHas('pedidos_titulares',['id'=>$p['referencia'],'situacao'=>'em_analise','versao'=>5,'resposta_referencia'=>null,'concluido_em'=>null]);
        $this->assertDatabaseCount('pedido_titular_recibos',5);
    }
    public function test_validade_recusa_passado_e_aceita_data_futura_sem_limite_timestamp_2038(): void
    {
        $p=$this->emVerificacao();$t=$this->token(2);$url='pedidos-titulares/'.$p['referencia'].'/verificacoes';
        $v=$this->conferencia('identidade',2);
        $this->chamar($t,'POST',$url,array_replace($v,['valida_ate'=>now()->subMinute()->toIso8601String()]))->assertUnprocessable();
        // Data de fronteira técnica, não uma política recomendada de validade.
        $this->chamar($t,'POST',$url,array_replace($v,['valida_ate'=>'2040-01-01T00:00:00+00:00']))->assertOk();
        $this->assertDatabaseHas('pedido_titular_verificacoes',['pedido_id'=>$p['referencia'],'valida_ate'=>'2040-01-01 00:00:00']);
    }
    private function alunosParaSujeito(): void
    {
        config(['espacos.ativo'=>true,'espacos.pessoal'=>true]);
        foreach ([1,2] as $id) {
            DB::table('turmas')->insert(['id'=>$id,'escola_id'=>null,'espaco_id'=>$id,'espaco_tipo'=>'pessoal',
                'nome'=>'Turma fictícia','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
            DB::table('alunos')->insert(['id'=>$id,'turma_id'=>$id,'espaco_id'=>$id,'espaco_tipo'=>'pessoal',
                'nome'=>'Aluno reservado fictício','matricula'=>'L4-'.$id]);
        }
    }
    private function sujeito(int $versao=1): array
    {
        return ['aluno_id'=>1,'versao'=>$versao,'evidencia_referencia'=>'Registro fictício de identificação',
            'motivo'=>'Vinculação revisada fictícia','chave'=>(string)Str::uuid()];
    }
    public function test_vinculacao_do_sujeito_cifra_historico_e_invalida_conferencias_anteriores(): void
    {
        $this->alunosParaSujeito();$p=$this->emVerificacao();$s=app(PedidosTitulares::class);
        $s->verificar($this->ator(2),$p['referencia'],$this->conferencia('identidade',2));
        $d=$this->sujeito(3);$r=$s->vincularSujeito($this->ator(2),$p['referencia'],$d);
        $this->assertSame(4,$r['versao']);
        $this->assertSame($r,$s->vincularSujeito($this->ator(2),$p['referencia'],$d));
        $this->assertDatabaseHas('pedidos_titulares',['id'=>$p['referencia'],'aluno_id'=>1,'sujeito_versao'=>1,'ciclo_verificacao'=>2]);
        $this->assertDatabaseCount('pedido_titular_sujeitos',1);
        $h=DB::table('pedido_titular_sujeitos')->first();
        $this->assertNotSame($d['evidencia_referencia'],$h->evidencia);
        $this->assertSame($d['evidencia_referencia'],Crypt::decryptString($h->evidencia));
        $atual=$s->consultar($this->ator(2),$p['referencia']);
        $this->assertSame([],$atual['verificacoes']);$this->assertSame(1,$atual['aluno_id']);
        $this->assertDatabaseCount('pedido_titular_verificacoes',1);
        $this->assertArrayNotHasKey('nome',$r);
        $this->assertDatabaseHas('alunos',['id'=>1,'nome'=>'Aluno reservado fictício','turma_id'=>1]);
    }
    public function test_sujeito_nao_enumera_outro_espaco_nem_admite_perfil_ou_atribuicao_incorretos(): void
    {
        $this->alunosParaSujeito();$p=app(PedidosTitulares::class)->abrir($this->ator(),$this->dados());
        $url='pedidos-titulares/'.$p['referencia'].'/sujeito';$d=$this->sujeito();$t=$this->token(2);
        foreach ([2,999999] as $aluno) $this->chamar($t,'POST',$url,array_replace($d,['aluno_id'=>$aluno]))->assertNotFound();
        foreach ([1,4] as $id) $this->chamar($this->token($id),'POST',$url,$d)->assertNotFound();
        $this->chamar($this->token(3),'POST',$url,$d)->assertForbidden();
        $this->chamar($t,'POST',$url,$d+['autoridade_verificada'=>true])->assertUnprocessable();
        $this->assertDatabaseCount('pedido_titular_sujeitos',0);
        config(['espacos.pessoal'=>false]);$this->chamar($t,'POST',$url,$d)->assertForbidden();
        config(['espacos.pessoal'=>true]);
        config(['espacos.ativo'=>false]);$this->chamar($t,'POST',$url,$d)->assertForbidden();
    }
    public function test_sujeito_escolar_exige_espaco_exato_e_hierarquia_ativa(): void
    {
        $this->alunosParaSujeito();
        DB::table('redes')->insert(['id'=>1,'nome'=>'Rede fictícia','tipo'=>'estadual','uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>1,'rede_id'=>1,'nome'=>'Núcleo fictício']);
        DB::table('escolas')->insert(['id'=>1,'nucleo_id'=>1,'nome'=>'Escola fictícia','inep'=>'00000001','tipo_rede'=>'estadual']);
        foreach (['rede'=>3,'nucleo'=>4,'escola'=>5] as $tipo=>$id) {
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>1,'tipo'=>$tipo,'nome'=>'Espaço fictício','situacao'=>'ativo',$tipo.'_id'=>1]);
        }
        DB::table('turmas')->insert(['id'=>3,'escola_id'=>1,'espaco_id'=>5,'nome'=>'Turma fictícia','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
        DB::table('alunos')->insert(['id'=>3,'turma_id'=>3,'espaco_id'=>5,'nome'=>'Aluno fictício','matricula'=>'L4-escolar']);
        $p=app(PedidosTitulares::class)->abrir($this->ator(),array_replace($this->dados(),['espaco_id'=>5]));
        $t=$this->token(2);$url='pedidos-titulares/'.$p['referencia'].'/sujeito';
        $this->chamar($t,'POST',$url,$this->sujeito())->assertNotFound(); // Mesmo cliente, outro espaço.
        $this->chamar($t,'POST',$url,array_replace($this->sujeito(),['aluno_id'=>3]))->assertOk();
        DB::table('espacos')->where('id',3)->update(['situacao'=>'suspenso']);
        $this->chamar($t,'POST',$url,array_replace($this->sujeito(2),['aluno_id'=>3]))->assertNotFound();
    }
    public function test_sujeito_rejeita_conflito_de_versao_payload_e_estado_e_preserva_historico(): void
    {
        $this->alunosParaSujeito();$p=app(PedidosTitulares::class)->abrir($this->ator(),$this->dados());
        $t=$this->token(2);$url='pedidos-titulares/'.$p['referencia'].'/sujeito';$d=$this->sujeito();
        $this->chamar($t,'POST',$url,array_replace($d,['versao'=>2]))->assertConflict();
        $this->chamar($t,'POST',$url,$d)->assertOk();
        $this->chamar($t,'POST',$url,array_replace($d,['motivo'=>'Outra justificativa']))->assertConflict();
        $this->chamar($t,'POST',$url,$this->sujeito(2))->assertOk();
        $this->assertDatabaseCount('pedido_titular_sujeitos',2);
        $this->assertDatabaseHas('pedidos_titulares',['id'=>$p['referencia'],'sujeito_versao'=>2,'ciclo_verificacao'=>3]);
        foreach (['em_analise','concluida','cancelada'] as $estado) {
            DB::table('pedidos_titulares')->where('id',$p['referencia'])->update(['situacao'=>$estado]);
            $this->chamar($t,'POST',$url,$this->sujeito(3))->assertUnprocessable();
        }
    }
    public function test_falha_do_evento_reverte_sujeito_ciclo_historico_e_recibo(): void
    {
        $this->alunosParaSujeito();$s=app(PedidosTitulares::class);$p=$s->abrir($this->ator(),$this->dados());
        DB::listen(function($q){
            if (str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'pedido_titular_eventos')) throw new \RuntimeException('Falha fictícia de histórico');
        });
        try {$s->vincularSujeito($this->ator(2),$p['referencia'],$this->sujeito());$this->fail('Deveria reverter');}
        catch (\RuntimeException $e) {$this->assertSame('Falha fictícia de histórico',$e->getMessage());}
        $this->assertDatabaseHas('pedidos_titulares',['id'=>$p['referencia'],'aluno_id'=>null,'sujeito_versao'=>0,'versao'=>1,'ciclo_verificacao'=>1]);
        $this->assertDatabaseCount('pedido_titular_sujeitos',0);$this->assertDatabaseCount('pedido_titular_recibos',1);
    }
    public function test_opcoes_e_permissoes_de_navegacao_nao_ampliam_acesso(): void
    {
        $dev=$this->token(1);$suporte=$this->token(2);$financeiro=$this->token(3);
        DB::table('usuario_acessos_internos')->where('id',4)->update(['fim_em'=>now()->subMinute()]);
        $r=$this->chamar($dev,'GET','pedidos-titulares/opcoes?cliente_id=1')->assertOk()
            ->assertJsonCount(2,'data.responsaveis.data')->assertJsonCount(1,'data.espacos.data');
        $this->assertSame([1,2],array_column($r->json('data.responsaveis.data'),'id'));
        $this->assertArrayNotHasKey('email',$r->json('data.responsaveis.data.0'));
        foreach ([$suporte,$financeiro] as $t) $this->chamar($t,'GET','pedidos-titulares/opcoes')->assertForbidden();
        $this->assertContains('pedido.abrir',$this->chamar($dev,'GET','auth/me')->assertOk()->json('data.permissoes'));
        $this->assertNotContains('pedido.abrir',$this->chamar($suporte,'GET','auth/me')->assertOk()->json('data.permissoes'));
        $this->assertNotContains('pedido.atender',$this->chamar($financeiro,'GET','auth/me')->assertOk()->json('data.permissoes'));
        config(['pedidos_titulares.ativo'=>false]);
        $this->assertNotContains('pedido.atender',$this->chamar($dev,'GET','auth/me')->assertOk()->json('data.permissoes'));
        $this->chamar($dev,'GET','pedidos-titulares/opcoes')->assertForbidden();
    }
}
