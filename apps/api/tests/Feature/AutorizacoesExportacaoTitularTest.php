<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Services\{AutorizacoesExportacaoTitular,PedidosTitulares};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Crypt};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AutorizacoesExportacaoTitularTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,
            'acessos_internos.ativo'=>true,'pedidos_titulares.ativo'=>true,'pedidos_titulares.autorizacoes_exportacao'=>true]);
        foreach (range(1,8) as $id) Usuario::forceCreate(['id'=>$id,'nome'=>'Pessoa fictícia','perfil'=>'admin_rede',
            'email'=>"c2-$id@example.test",'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
        foreach ([1=>'desenvolvedor',2=>'suporte',8=>'financeiro'] as $id=>$perfil) DB::table('usuario_acessos_internos')->insert([
            'id'=>$id,'usuario_id'=>$id,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        foreach ([1,2] as $id) {
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Cliente fictício','modalidade'=>'teste','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>'Pessoal fictício','situacao'=>'ativo','usuario_proprietario_id'=>$id+2]);
            $this->aluno($id,$id,null);
        }
        foreach ([1,2] as $rede) {
            DB::table('redes')->insert(['id'=>$rede,'nome'=>'Rede fictícia','tipo'=>'estadual','uf'=>'BA']);
            DB::table('nucleos')->insert(['id'=>$rede,'rede_id'=>$rede,'nome'=>'Núcleo fictício']);
            DB::table('escolas')->insert(['id'=>$rede,'nucleo_id'=>$rede,'nome'=>'Escola fictícia','inep'=>'0000000'.$rede,'tipo_rede'=>'estadual']);
            foreach (['rede'=>0,'nucleo'=>1,'escola'=>2] as $tipo=>$offset) DB::table('espacos')->insert([
                'id'=>$rede*3+$offset,'cliente_id'=>1,'tipo'=>$tipo,'nome'=>'Espaço fictício','situacao'=>'ativo',$tipo.'_id'=>$rede]);
            $this->aluno($rede*3+2,$rede*3+2,$rede);
        }
        foreach ([[3,1,'professor_autonomo'],[4,2,'professor_autonomo'],[5,3,'admin_rede'],[6,5,'dir_escolar'],[7,6,'admin_rede']] as [$u,$e,$p]) {
            DB::table('usuario_vinculos')->insert(['id'=>$u,'usuario_id'=>$u,'espaco_id'=>$e,'perfil'=>$p,
                'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
    }
    private function aluno(int $id,int $espaco,?int $escola): void
    {
        DB::table('turmas')->insert(['id'=>$id,'escola_id'=>$escola,'espaco_id'=>$espaco,'espaco_tipo'=>$escola?'escola':'pessoal',
            'nome'=>'Turma fictícia','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
        DB::table('alunos')->insert(['id'=>$id,'turma_id'=>$id,'espaco_id'=>$espaco,'espaco_tipo'=>$escola?'escola':'pessoal',
            'nome'=>'Aluno reservado fictício','matricula'=>'C2-'.$id]);
    }
    private function interno(int $id=2): Usuario {return Usuario::find($id)->noAcessoInterno($id,1);}
    private function gestor(int $id=3): Usuario {return Usuario::find($id)->noVinculo($id);}
    private function protocolo(int $espaco=1): array
    {
        $s=app(PedidosTitulares::class);$u=$this->interno();
        $p=$s->abrir($this->interno(1),['cliente_id'=>$espaco===2?2:1,'espaco_id'=>$espaco,'responsavel_acesso_id'=>2,
            'tipo'=>'acesso','identificacao_declarada'=>'Declaração fictícia','relato'=>'Relato privado fictício','motivo'=>'Atendimento fictício','chave'=>(string)Str::uuid()]);
        $id=$p['referencia'];
        $p=$s->vincularSujeito($u,$id,['aluno_id'=>$espaco,'versao'=>$p['versao'],'evidencia_referencia'=>'Referência fictícia','motivo'=>'Sujeito fictício','chave'=>(string)Str::uuid()]);
        foreach (['identidade'=>'conferencia_documental','autoridade'=>'confirmacao_institucional'] as $tipo=>$metodo) {
            $p=$s->verificar($u,$id,['tipo'=>$tipo,'metodo'=>$metodo,'versao'=>$p['versao'],'evidencia_referencia'=>'Evidência fictícia',
                'motivo'=>'Conferência fictícia','valida_ate'=>now()->addHours(2)->toIso8601String(),'chave'=>(string)Str::uuid()]);
        }
        return $s->decidir($u,$id,['situacao'=>'em_analise','versao'=>$p['versao'],'motivo'=>'Análise fictícia','chave'=>(string)Str::uuid()]);
    }
    private function dados(array $p,int $aluno=1): array
    {
        return ['versao'=>$p['versao'],'aluno_id'=>$aluno,'sujeito_versao'=>1,'destinatario_acesso_id'=>2,
            'campos'=>['nome','matricula'],'valida_ate'=>now()->addHour()->toIso8601String(),
            'motivo'=>'Liberação fictícia','evidencia_referencia'=>'Autorização privada fictícia','chave'=>(string)Str::uuid()];
    }
    private function s(): AutorizacoesExportacaoTitular {return app(AutorizacoesExportacaoTitular::class);}
    private function negar(callable $f,int $status): void
    {
        try {$f();$this->fail('Operação deveria ser recusada');}
        catch (HttpException $e) {$this->assertSame($status,$e->getStatusCode());}
        catch (ValidationException $e) {$this->assertSame($status,422);}
    }
    public function test_proprietario_concede_e_reenvia_sem_expor_dados_e_destinatario_revalida(): void
    {
        $p=$this->protocolo();$d=$this->dados($p);$u=$this->gestor();
        $pre=$this->s()->consultarPedido($u,$p['referencia']);$this->assertSame(1,$pre['aluno_id']);$this->assertArrayNotHasKey('relato',$pre);
        $r=$this->s()->conceder($u,$p['referencia'],$d);
        $this->assertSame($r,$this->s()->conceder($u,$p['referencia'],$d));$this->assertDatabaseCount('pedido_exportacao_autorizacoes',1);
        $g=DB::table('pedido_exportacao_autorizacoes')->first();
        $this->assertNotSame($d['evidencia_referencia'],$g->evidencia);$this->assertSame($d['evidencia_referencia'],Crypt::decryptString($g->evidencia));
        $this->assertStringNotContainsString('Aluno reservado',json_encode($r));
        $out=$this->s()->comAutorizacao($this->interno(),$r['referencia'],fn($contexto)=>$contexto);
        $this->assertSame(['matricula','nome'],$out['campos']);$this->assertSame(1,$out['aluno_id']);
        $this->assertArrayNotHasKey('nome',$out);$this->assertDatabaseCount('pedido_exportacao_eventos',2);
        $this->negar(fn()=>$this->s()->conceder($u,$p['referencia'],array_replace($d,['motivo'=>'Divergente'])),409);
        $this->negar(fn()=>$this->s()->conceder($u,$p['referencia'],array_replace($d,['chave'=>(string)Str::uuid(),'versao'=>6])),409);
    }
    public function test_administrador_so_aprova_na_propria_rede_mesmo_com_cliente_compartilhado(): void
    {
        $p=$this->protocolo(5);$outro=$this->protocolo(8);
        $this->negar(fn()=>$this->s()->conceder($this->gestor(7),$p['referencia'],$this->dados($p,5)),404);
        $this->negar(fn()=>$this->s()->conceder($this->gestor(5),$outro['referencia'],$this->dados($outro,8)),404);
        DB::table('perfil_permissoes')->insert(['perfil'=>'dir_escolar','permissao'=>'pedido.exportacao.autorizar','tipo'=>'padrao']);
        $this->negar(fn()=>$this->s()->conceder($this->gestor(6),$p['referencia'],$this->dados($p,5)),403);
        $r=$this->s()->conceder($this->gestor(5),$p['referencia'],$this->dados($p,5));
        $this->assertSame(5,$this->s()->comAutorizacao($this->interno(),$r['referencia'],fn($c)=>$c['aluno_id']));
    }
    public function test_internos_legado_outro_proprietario_e_payload_indevido_nao_concedem(): void
    {
        $p=$this->protocolo();$d=$this->dados($p);
        foreach ([$this->interno(1),$this->interno(2),$this->interno(8),Usuario::find(3)] as $u) $this->negar(fn()=>$this->s()->conceder($u,$p['referencia'],$d),403);
        $this->negar(fn()=>$this->s()->conceder($this->gestor(4),$p['referencia'],$d),404);
        foreach ([['campos'=>['cpf']],['campos'=>[]],['campos'=>['nome','nome']],['valida_ate'=>now()->addDays(2)->toIso8601String()],
            ['valida_ate'=>now()->toIso8601String()],['destinatario_acesso_id'=>1],['autoridade'=>true]] as $mudanca) {
            $this->negar(fn()=>$this->s()->conceder($this->gestor(),$p['referencia'],array_replace($d,$mudanca)),422);
        }
        foreach ([['aluno_id'=>2],['sujeito_versao'=>2],['versao'=>99]] as $mudanca) $this->negar(fn()=>$this->s()->conceder($this->gestor(),$p['referencia'],array_replace($d,$mudanca)),409);
        $this->assertDatabaseCount('pedido_exportacao_autorizacoes',0);
    }
    public static function invalidacoes(): array
    {
        return array_map(fn($v)=>[$v],['expiracao','conferencia','ciclo','sujeito','responsavel','vinculo','vinculo_revogado','permissao','aluno','flag','proprietario','conclusao']);
    }
    #[DataProvider('invalidacoes')]
    public function test_uso_revalida_todas_as_condicoes_e_nao_executa_callback(string $caso): void
    {
        $p=$this->protocolo();$d=$this->dados($p);$r=$this->s()->conceder($this->gestor(),$p['referencia'],$d);
        match($caso) {
            'expiracao'=>$this->travelTo(\Illuminate\Support\Carbon::parse($d['valida_ate'])),
            'conferencia'=>DB::table('pedido_titular_verificacoes')->update(['valida_ate'=>now()->subSecond()]),
            'ciclo'=>DB::table('pedidos_titulares')->where('id',$p['referencia'])->increment('ciclo_verificacao'),
            'sujeito'=>DB::table('pedidos_titulares')->where('id',$p['referencia'])->increment('sujeito_versao'),
            'responsavel'=>DB::table('usuario_acessos_internos')->where('id',2)->increment('versao_autorizacao'),
            'vinculo'=>DB::table('usuario_vinculos')->where('id',3)->increment('versao_autorizacao'),
            'vinculo_revogado'=>DB::table('usuario_vinculos')->where('id',3)->update(['situacao'=>'revogado']),
            'permissao'=>DB::table('vinculo_permissoes')->insert(['vinculo_id'=>3,'permissao'=>'pedido.exportacao.autorizar','efeito'=>'retirar']),
            'aluno'=>DB::table('alunos')->where('id',1)->update(['updated_at'=>now()->addMinute()]),
            'flag'=>config(['pedidos_titulares.autorizacoes_exportacao'=>false]),
            'proprietario'=>DB::table('usuarios')->where('id',3)->update(['ativo'=>false]),
            'conclusao'=>DB::table('pedidos_titulares')->where('id',$p['referencia'])->update(['situacao'=>'concluida']),
        };
        $executou=false;
        try {$this->s()->comAutorizacao($this->interno(),$r['referencia'],function()use(&$executou){$executou=true;});$this->fail('Uso indevido');}
        catch (HttpException $e) {$this->assertContains($e->getStatusCode(),[403,404,409]);}
        finally {$this->travelBack();}
        $this->assertFalse($executou);$this->assertDatabaseCount('pedido_exportacao_eventos',1);
    }
    public function test_revogacao_idempotente_bloqueia_uso_sem_apagar_historico(): void
    {
        $p=$this->protocolo();$d=$this->dados($p);$r=$this->s()->conceder($this->gestor(),$p['referencia'],$d);
        $this->negar(fn()=>$this->s()->comAutorizacao($this->interno(1),$r['referencia'],fn()=>null),404);
        $v=['versao'=>1,'motivo'=>'Revogação fictícia','chave'=>(string)Str::uuid()];
        $this->negar(fn()=>$this->s()->revogar($this->gestor(4),$r['referencia'],$v),404);
        $rev=$this->s()->revogar($this->gestor(),$r['referencia'],$v);
        $this->assertSame($rev,$this->s()->revogar($this->gestor(),$r['referencia'],$v));
        $this->assertSame($r,$this->s()->conceder($this->gestor(),$p['referencia'],$d)); // Recibo antigo não reativa.
        $this->negar(fn()=>$this->s()->comAutorizacao($this->interno(),$r['referencia'],fn()=>null),403);
        $this->assertDatabaseCount('pedido_exportacao_eventos',2);$this->assertDatabaseCount('pedido_exportacao_autorizacoes',1);
    }
    public function test_falha_do_evento_reverte_concessao_versao_e_recibo(): void
    {
        $p=$this->protocolo();
        DB::listen(function($q){if(str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'pedido_exportacao_eventos'))throw new \RuntimeException('Falha fictícia');});
        try {$this->s()->conceder($this->gestor(),$p['referencia'],$this->dados($p));$this->fail('Deveria falhar');}
        catch (\RuntimeException $e) {$this->assertSame('Falha fictícia',$e->getMessage());}
        $this->assertDatabaseCount('pedido_exportacao_autorizacoes',0);$this->assertDatabaseCount('pedido_exportacao_recibos',0);
        $this->assertDatabaseHas('pedidos_titulares',['id'=>$p['referencia'],'versao'=>$p['versao']]);
    }
    public function test_prazo_respeita_fim_do_vinculo_e_da_atribuicao(): void
    {
        $p=$this->protocolo();$d=$this->dados($p);
        DB::table('usuario_vinculos')->where('id',3)->update(['fim_em'=>now()->addMinutes(30)]);
        $this->negar(fn()=>$this->s()->conceder($this->gestor(),$p['referencia'],$d),422);
        DB::table('usuario_vinculos')->where('id',3)->update(['fim_em'=>null]);
        DB::table('pedidos_titulares')->where('id',$p['referencia'])->update(['atribuicao_fim_em'=>now()->addMinutes(30)]);
        $this->negar(fn()=>$this->s()->conceder($this->gestor(),$p['referencia'],$d),422);
        $this->assertDatabaseCount('pedido_exportacao_autorizacoes',0);
    }
    public function test_falha_da_auditoria_impede_uso_e_reverte_revogacao(): void
    {
        $p=$this->protocolo();$r=$this->s()->conceder($this->gestor(),$p['referencia'],$this->dados($p));
        DB::listen(function($q){if(str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'pedido_exportacao_eventos'))throw new \RuntimeException('Falha fictícia');});
        $executou=false;
        try {$this->s()->comAutorizacao($this->interno(),$r['referencia'],function()use(&$executou){$executou=true;});$this->fail('Deveria falhar');}
        catch (\RuntimeException $e) {$this->assertSame('Falha fictícia',$e->getMessage());}
        $this->assertFalse($executou);
        try {$this->s()->revogar($this->gestor(),$r['referencia'],['versao'=>1,'motivo'=>'Revogação fictícia','chave'=>(string)Str::uuid()]);$this->fail('Deveria falhar');}
        catch (\RuntimeException $e) {$this->assertSame('Falha fictícia',$e->getMessage());}
        $this->assertDatabaseHas('pedido_exportacao_autorizacoes',['id'=>$r['referencia'],'versao'=>1,'revogada_em'=>null]);
        $this->assertDatabaseCount('pedido_exportacao_eventos',1);$this->assertDatabaseCount('pedido_exportacao_recibos',1);
    }
    private function chamar(string $token,string $method,string $url,array $d=[])
    {
        app('auth')->forgetGuards();return $this->json($method,'/api/v1/'.$url,$d,['Authorization'=>'Bearer '.$token]);
    }
    public function test_http_exige_contexto_e_nao_abre_excecao_para_internos(): void
    {
        $p=$this->protocolo();$identidade=Usuario::find(3)->createToken('api-token')->plainTextToken;
        $token=$this->chamar($identidade,'POST','auth/vinculos/selecionar',['vinculo_id'=>3])->assertCreated()->json('data.token');
        $url='autorizacoes-exportacao/pedidos/'.$p['referencia'];
        $this->chamar($identidade,'GET',$url)->assertForbidden();
        $this->chamar($token,'GET',$url)->assertOk()->assertJsonMissing(['relato'=>'Relato privado fictício']);
        $r=$this->chamar($token,'POST',$url,$this->dados($p))->assertCreated()->json('data');
        $this->chamar($token,'POST','autorizacoes-exportacao/'.$r['referencia'].'/revogar',[
            'versao'=>1,'motivo'=>'Revogação fictícia','chave'=>(string)Str::uuid()])->assertOk()->assertJsonPath('data.revogada',true);
        $interno=Usuario::find(1)->createToken('api-token')->plainTextToken;
        $interno=$this->chamar($interno,'POST','auth/acessos-internos/selecionar',['acesso_interno_id'=>1])->assertCreated()->json('data.token');
        $this->chamar($interno,'GET',$url)->assertForbidden();
        config(['pedidos_titulares.autorizacoes_exportacao'=>false]);$this->chamar($token,'GET',$url)->assertForbidden();
    }
}
