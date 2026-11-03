<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\GeracaoExportacaoTitular;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Crypt,DB};
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GeracaoExportacaoTitularTest extends TestCase
{
    use RefreshDatabase, \Tests\Support\PreparaExportacaoTitular;

    private function concessao(int $espaco=1,int $gestor=3,array $campos=['nome','matricula']): array
    {
        config(['pedidos_titulares.geracao_exportacao'=>true]);
        $p=$this->protocolo($espaco);$d=$this->dados($p,$espaco);$d['campos']=$campos;
        return $this->s()->conceder($this->gestor($gestor),$p['referencia'],$d);
    }
    private function entrada(): array {return ['versao'=>1,'chave'=>(string)Str::uuid()];}
    private function gerar(string $id,array $d): array {return app(GeracaoExportacaoTitular::class)->gerar($this->interno(),$id,$d);}

    public function test_documento_cifrado_manifesto_campos_e_recibo_idempotente(): void
    {
        $g=$this->concessao(1,3,['matricula']);$d=$this->entrada();$r=$this->gerar($g['referencia'],$d);
        $this->assertSame($r,$this->gerar($g['referencia'],$d));
        $this->assertSame(['referencia','situacao','expira_em'],array_keys($r));
        $this->assertSame('disponivel',$r['situacao']);$this->assertSame($g['valida_ate'],$r['expira_em']);
        $a=DB::table('pedido_exportacao_artefatos')->first();
        $this->assertSame(hash('sha256',$a->conteudo_cifrado),$a->sha256_cifrado);
        $this->assertStringNotContainsString('C2-1',$a->conteudo_cifrado);
        $doc=json_decode(Crypt::decryptString($a->conteudo_cifrado),true,512,JSON_THROW_ON_ERROR);
        $this->assertSame(['matricula'=>'C2-1'],$doc['dados']);
        $this->assertSame(['matricula'],$doc['manifesto']['campos']);
        $this->assertSame('cadastro_minimo_v1',$doc['manifesto']['formato']);
        $this->assertSame($r['referencia'],$doc['manifesto']['referencia']);
        $this->assertSame(hash('sha256',json_encode($doc['dados'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),$doc['manifesto']['sha256_dados']);
        $this->assertDatabaseCount('pedido_exportacao_artefatos',1);
        $this->assertSame(['solicitada','gerando','disponivel'],DB::table('pedido_exportacao_artefato_eventos')->orderBy('id')->pluck('acao')->all());
        $this->negar(fn()=>$this->gerar($g['referencia'],$this->entrada()),409);
    }

    public function test_duas_instituicoes_nao_misturam_conteudo_nem_reutilizam_chave(): void
    {
        $g1=$this->concessao(5,5);$g2=$this->concessao(8,7);$d=$this->entrada();
        $this->gerar($g1['referencia'],$d);
        $this->negar(fn()=>$this->gerar($g2['referencia'],$d),409);
        $this->gerar($g2['referencia'],$this->entrada());
        foreach ([$g1['referencia']=>'C2-5',$g2['referencia']=>'C2-8'] as $id=>$matricula) {
            $a=DB::table('pedido_exportacao_artefatos')->where('autorizacao_id',$id)->first();
            $doc=json_decode(Crypt::decryptString($a->conteudo_cifrado),true);
            $this->assertSame(['matricula'=>$matricula,'nome'=>'Aluno reservado fictício'],$doc['dados']);
            $this->assertSame($id,$doc['manifesto']['autorizacao']);
        }
        $this->assertDatabaseCount('pedido_exportacao_artefatos',2);
    }

    public function test_atores_campos_extras_versao_e_flags_sao_recusados(): void
    {
        $g=$this->concessao();$s=app(GeracaoExportacaoTitular::class);
        foreach ([$this->interno(1),$this->interno(8),$this->gestor(),Usuario::find(2)] as $u) {
            try {$s->gerar($u,$g['referencia'],$this->entrada());$this->fail('Acesso indevido');}
            catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {$this->assertContains($e->getStatusCode(),[403,404]);}
        }
        $this->negar(fn()=>$this->gerar($g['referencia'],array_replace($this->entrada(),['versao'=>2])),409);
        $this->negar(fn()=>$this->gerar($g['referencia'],$this->entrada()+['campos'=>['cpf']]),422);
        config(['pedidos_titulares.geracao_exportacao'=>false]);
        $this->negar(fn()=>$this->gerar($g['referencia'],$this->entrada()),403);
        $this->assertDatabaseCount('pedido_exportacao_artefatos',0);
    }

    public static function bloqueios(): array {return [['revogada'],['expirada'],['transferido']];}
    #[DataProvider('bloqueios')]
    public function test_reenvio_revalida_sem_renovar_arquivo(string $caso): void
    {
        $g=$this->concessao();$d=$this->entrada();$this->gerar($g['referencia'],$d);
        match($caso) {
            'revogada'=>$this->s()->revogar($this->gestor(),$g['referencia'],['versao'=>1,'motivo'=>'Revogação fictícia','chave'=>(string)Str::uuid()]),
            'expirada'=>$this->travelTo(\Illuminate\Support\Carbon::parse($g['valida_ate'])),
            'transferido'=>DB::table('alunos')->where('id',1)->update(['espaco_id'=>2,'turma_id'=>2]),
        };
        try {
            try {$this->gerar($g['referencia'],$d);$this->fail('Reenvio indevido');}
            catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {$this->assertContains($e->getStatusCode(),[403,404]);}
            $this->assertDatabaseCount('pedido_exportacao_artefatos',1);
            $this->assertDatabaseCount('pedido_exportacao_artefato_eventos',3);
        } finally {$this->travelBack();}
    }

    public static function falhas(): array {return [['conteudo_cifrado'],['pedido_exportacao_artefato_eventos'],['evento_final']];}
    #[DataProvider('falhas')]
    public function test_falha_de_gravacao_ou_evento_reverte_tudo_e_permite_repeticao(string $alvo): void
    {
        $g=$this->concessao();$d=$this->entrada();$falhar=true;
        DB::listen(function($q)use($alvo,&$falhar){
            $atingido=$alvo==='evento_final'
                ? str_contains($q->sql,'pedido_exportacao_artefato_eventos') && in_array('disponivel',$q->bindings,true)
                : str_contains($q->sql,$alvo);
            if ($falhar && preg_match('/^(insert|update)/i',$q->sql) && $atingido) {
                $falhar=false;throw new \RuntimeException('Falha fictícia');
            }
        });
        try {$this->gerar($g['referencia'],$d);$this->fail('Deveria falhar');}
        catch (\RuntimeException $e) {$this->assertSame('Falha fictícia',$e->getMessage());}
        $this->assertDatabaseCount('pedido_exportacao_artefatos',0);
        $this->assertDatabaseCount('pedido_exportacao_artefato_eventos',0);
        $this->assertDatabaseCount('pedido_exportacao_eventos',1);
        $this->assertSame('disponivel',$this->gerar($g['referencia'],$d)['situacao']);
    }

    public function test_expiracao_durante_a_geracao_reverte_artefato_e_eventos(): void
    {
        $g=$this->concessao();$avancar=true;
        DB::listen(function($q)use($g,&$avancar){
            if ($avancar && str_starts_with(strtolower($q->sql),'update') && str_contains($q->sql,'conteudo_cifrado')) {
                $avancar=false;$this->travelTo(\Illuminate\Support\Carbon::parse($g['valida_ate']));
            }
        });
        try {$this->negar(fn()=>$this->gerar($g['referencia'],$this->entrada()),403);}
        finally {$this->travelBack();}
        $this->assertDatabaseCount('pedido_exportacao_artefatos',0);
        $this->assertDatabaseCount('pedido_exportacao_artefato_eventos',0);
        $this->assertDatabaseCount('pedido_exportacao_eventos',1);
    }

    public function test_conteudo_corrompido_na_gravacao_nunca_fica_disponivel(): void
    {
        $g=$this->concessao();$alterar=true;
        DB::listen(function($q)use(&$alterar){
            if ($alterar && str_starts_with(strtolower($q->sql),'update') && str_contains($q->sql,'conteudo_cifrado')) {
                $alterar=false;DB::table('pedido_exportacao_artefatos')->update(['conteudo_cifrado'=>'adulterado']);
            }
        });
        $this->negar(fn()=>$this->gerar($g['referencia'],$this->entrada()),503);
        $this->assertDatabaseCount('pedido_exportacao_artefatos',0);
        $this->assertDatabaseCount('pedido_exportacao_artefato_eventos',0);
    }

    public function test_indices_impedem_duplicacao_de_concessao_e_chave(): void
    {
        $g=$this->concessao();$g2=$this->concessao(2,4);$this->gerar($g['referencia'],$this->entrada());
        $a=(array)DB::table('pedido_exportacao_artefatos')->first();
        foreach ([['chave'=>(string)Str::uuid()],['autorizacao_id'=>$g2['referencia']]] as $mudanca) {
            try {
                DB::transaction(fn()=>DB::table('pedido_exportacao_artefatos')->insert(array_replace($a,$mudanca,['id'=>(string)Str::uuid()])));
                $this->fail('Índice deveria impedir duplicação');
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {$this->assertNotEmpty($e->getCode());}
        }
        $this->assertDatabaseCount('pedido_exportacao_artefatos',1);
    }

    public function test_http_exige_contexto_responsavel_e_nao_entrega_conteudo(): void
    {
        $g=$this->concessao();$identidade=Usuario::find(2)->createToken('api-token')->plainTextToken;
        $url='/api/v1/pedidos-titulares/exportacoes/'.$g['referencia'];
        $this->withToken($identidade)->postJson($url,$this->entrada())->assertForbidden();
        app('auth')->forgetGuards();
        $token=$this->withToken($identidade)->postJson('/api/v1/auth/acessos-internos/selecionar',['acesso_interno_id'=>2])->assertCreated()->json('data.token');
        app('auth')->forgetGuards();
        $res=$this->withToken($token)->postJson($url,$this->entrada())->assertCreated()->assertJsonPath('data.situacao','disponivel');
        $this->assertSame(['referencia','situacao','expira_em'],array_keys($res->json('data')));
        $this->assertStringNotContainsString('Aluno reservado',$res->getContent());
    }
}
