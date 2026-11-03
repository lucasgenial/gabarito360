<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\{EntregaExportacaoTitular,GeracaoExportacaoTitular};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\{Carbon,Str};
use Illuminate\Support\Facades\{Crypt,DB};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EntregaExportacaoTitularTest extends TestCase
{
    use RefreshDatabase, \Tests\Support\PreparaExportacaoTitular;
    private const JSON=JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR;
    private function artefato(int $espaco=1,int $gestor=3): array
    {
        config(['pedidos_titulares.geracao_exportacao'=>true,'pedidos_titulares.entrega_exportacao'=>true]);
        $p=$this->protocolo($espaco);$d=$this->dados($p,$espaco);$d['campos']=['matricula'];
        $g=$this->s()->conceder($this->gestor($gestor),$p['referencia'],$d);
        $a=app(GeracaoExportacaoTitular::class)->gerar($this->interno(),$g['referencia'],['versao'=>1,'chave'=>(string)Str::uuid()]);
        return [$a['referencia'],$g['referencia']];
    }
    private function entrega(): EntregaExportacaoTitular {return app(EntregaExportacaoTitular::class);}
    private function eventoCount(): int {return DB::table('pedido_exportacao_artefato_eventos')->count();}

    public function test_consulta_minima_download_fiel_e_auditoria_a_cada_acesso(): void
    {
        [$id]=$this->artefato();$s=$this->entrega();
        config(['pedidos_titulares.geracao_exportacao'=>false]); // Entrega tem controle próprio.
        $r=$s->consultar($this->interno(),$id);
        $this->assertSame(['referencia','situacao','formato','campos','expira_em'],array_keys($r));
        $this->assertSame(['matricula'],$r['campos']);
        $this->assertStringNotContainsString('C2-1',json_encode($r));
        $original=Crypt::decryptString(DB::table('pedido_exportacao_artefatos')->where('id',$id)->value('conteudo_cifrado'));
        foreach ([1,2] as $_) $this->assertSame($original,$s->baixar($this->interno(),$id)['documento']);
        $this->assertSame(['solicitada','gerando','disponivel','consulta_validada','download_liberado','download_liberado'],
            DB::table('pedido_exportacao_artefato_eventos')->orderBy('id')->pluck('acao')->all());
    }

    public function test_contextos_alheios_e_versao_do_artefato_nao_recebem_dados(): void
    {
        [$id]=$this->artefato();
        foreach ([$this->interno(1),$this->interno(8),$this->gestor(),Usuario::find(2)] as $u) {
            foreach (['consultar','baixar'] as $metodo) {
                try {$this->entrega()->$metodo($u,$id);$this->fail('Acesso indevido');}
                catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {$this->assertContains($e->getStatusCode(),[403,404]);}
            }
        }
        DB::table('pedido_exportacao_artefatos')->where('id',$id)->increment('autorizacao_versao');
        $this->negar(fn()=>$this->entrega()->baixar($this->interno(),$id),404);
        $this->assertSame(3,$this->eventoCount());
    }

    public static function invalidacoes(): array
    {
        return array_map(fn($v)=>[$v],['revogada','expirada','transferido','conferencia','responsavel','emissor','estado','prazo','flag']);
    }
    #[DataProvider('invalidacoes')]
    public function test_revalida_autorizacao_e_prazo_em_consulta_e_download(string $caso): void
    {
        [$id,$g]=$this->artefato();
        match($caso) {
            'revogada'=>$this->s()->revogar($this->gestor(),$g,['versao'=>1,'motivo'=>'Revogação fictícia','chave'=>(string)Str::uuid()]),
            'expirada'=>$this->travelTo(Carbon::parse(DB::table('pedido_exportacao_artefatos')->where('id',$id)->value('expira_em'))),
            'transferido'=>DB::table('alunos')->where('id',1)->update(['espaco_id'=>2,'turma_id'=>2]),
            'conferencia'=>DB::table('pedido_titular_verificacoes')->update(['valida_ate'=>now()->subSecond()]),
            'responsavel'=>DB::table('usuario_acessos_internos')->where('id',2)->increment('versao_autorizacao'),
            'emissor'=>DB::table('usuario_vinculos')->where('id',3)->update(['situacao'=>'encerrado']),
            'estado'=>DB::table('pedido_exportacao_artefatos')->where('id',$id)->update(['situacao'=>'revogada']),
            'prazo'=>DB::table('pedido_exportacao_artefatos')->where('id',$id)->update(['expira_em'=>now()->addDays(2)]),
            'flag'=>config(['pedidos_titulares.entrega_exportacao'=>false]),
        };
        try {
            foreach (['consultar','baixar'] as $metodo) {
                try {$this->entrega()->$metodo($this->interno(),$id);$this->fail('Entrega indevida');}
                catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {$this->assertContains($e->getStatusCode(),[403,404,409]);}
            }
            $this->assertSame(3,$this->eventoCount());
        } finally {$this->travelBack();}
    }

    public static function adulteracoes(): array
    {
        return array_map(fn($v)=>[$v],['hash','mac','json','grande','referencia','autorizacao','formato','campos','extra','dados','hash_dados','data','expira']);
    }
    #[DataProvider('adulteracoes')]
    public function test_integridade_fecha_entrega_sem_revelar_conteudo(string $caso): void
    {
        [$id]=$this->artefato();$a=DB::table('pedido_exportacao_artefatos')->where('id',$id)->first();
        $doc=json_decode(Crypt::decryptString($a->conteudo_cifrado),true);
        if ($caso==='hash') $update=['sha256_cifrado'=>str_repeat('0',64)];
        elseif ($caso==='mac') $update=['conteudo_cifrado'=>'cifra-invalida','sha256_cifrado'=>hash('sha256','cifra-invalida')];
        else {
            switch ($caso) {
                case 'referencia':$doc['manifesto']['referencia']=(string)Str::uuid();break;
                case 'autorizacao':$doc['manifesto']['autorizacao']=(string)Str::uuid();break;
                case 'formato':$doc['manifesto']['formato']='novo';break;
                case 'campos':$doc['manifesto']['campos']=['nome'];break;
                case 'extra':$doc['manifesto']['privado']='DADO-NAO-EXIBIR';break;
                case 'dados':$doc['dados']['cpf']='DADO-NAO-EXIBIR';break;
                case 'hash_dados':$doc['manifesto']['sha256_dados']=str_repeat('0',64);break;
                case 'data':$doc['manifesto']['gerado_em']='data-invalida';break;
                case 'expira':$doc['manifesto']['expira_em']=now()->addDays(2)->toIso8601String();break;
            }
            $texto=match($caso){'json'=>'{DADO-NAO-EXIBIR','grande'=>str_repeat('x',17000),default=>json_encode($doc,self::JSON)};
            $cifrado=Crypt::encryptString($texto);$update=['conteudo_cifrado'=>$cifrado,'sha256_cifrado'=>hash('sha256',$cifrado)];
        }
        DB::table('pedido_exportacao_artefatos')->where('id',$id)->update($update);
        foreach (['consultar','baixar'] as $metodo) {
            try {$this->entrega()->$metodo($this->interno(),$id);$this->fail('Integridade deveria falhar');}
            catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(409,$e->getStatusCode());$this->assertSame('Exportação indisponível para entrega.',$e->getMessage());
            }
        }
        $this->assertSame(3,$this->eventoCount());
    }

    public function test_troca_de_conteudo_entre_instituicoes_e_bloqueada(): void
    {
        [$um]=$this->artefato(5,5);[$dois]=$this->artefato(8,7);
        $a=DB::table('pedido_exportacao_artefatos')->where('id',$dois)->first();
        $doc=json_decode($this->entrega()->baixar($this->interno(),$um)['documento'],true);
        $this->assertSame(['matricula'=>'C2-5'],$doc['dados']);
        DB::table('pedido_exportacao_artefatos')->where('id',$um)->update(['conteudo_cifrado'=>$a->conteudo_cifrado,'sha256_cifrado'=>$a->sha256_cifrado]);
        $this->negar(fn()=>$this->entrega()->baixar($this->interno(),$um),409);
    }

    public static function finais(): array {return [['falha'],['expiracao']];}
    #[DataProvider('finais')]
    public function test_falha_final_impede_retorno_e_reverte_eventos(string $caso): void
    {
        [$id]=$this->artefato();$antes=DB::table('pedido_exportacao_eventos')->count();
        DB::listen(function($q)use($caso,$id){
            if (str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'pedido_exportacao_artefato_eventos')
                && in_array('download_liberado',$q->bindings,true)) {
                if ($caso==='falha') throw new \RuntimeException('Falha fictícia');
                $this->travelTo(Carbon::parse(DB::table('pedido_exportacao_artefatos')->where('id',$id)->value('expira_em')));
            }
        });
        try {
            try {$this->entrega()->baixar($this->interno(),$id);$this->fail('Não deveria retornar');}
            catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {$this->assertSame(403,$e->getStatusCode());}
            catch (\RuntimeException $e) {$this->assertSame('Falha fictícia',$e->getMessage());}
            $this->assertSame(3,$this->eventoCount());
            $this->assertSame($antes,DB::table('pedido_exportacao_eventos')->count());
        } finally {$this->travelBack();}
    }

    private function chamar(?string $token,string $metodo,string $url,array $headers=[])
    {
        app('auth')->forgetGuards();
        return $this->json($metodo,$url,[],$headers+($token?['Authorization'=>'Bearer '.$token]:[]));
    }
    public function test_http_download_autenticado_anexo_sem_cache_sem_304_ou_range(): void
    {
        [$id]=$this->artefato();$base='/api/v1/pedidos-titulares/exportacoes/'.$id;
        $this->chamar(null,'GET',$base.'/arquivo')->assertUnauthorized()->assertHeader('Cache-Control','no-store, private');
        $identidade=Usuario::find(2)->createToken('api-token')->plainTextToken;
        $this->chamar($identidade,'GET',$base.'/arquivo')->assertForbidden();
        app('auth')->forgetGuards();
        $token=$this->withToken($identidade)->postJson('/api/v1/auth/acessos-internos/selecionar',['acesso_interno_id'=>2])->assertCreated()->json('data.token');
        $r=$this->chamar($token,'GET',$base)->assertOk()->assertJsonPath('data.campos',['matricula']);
        $this->assertStringNotContainsString('C2-1',$r->getContent());
        $r=$this->chamar($token,'GET',$base.'/arquivo',['Range'=>'bytes=0-1','If-None-Match'=>'*'])
            ->assertOk()->assertHeader('Cache-Control','no-store, private')->assertHeader('Pragma','no-cache')
            ->assertHeader('X-Content-Type-Options','nosniff')->assertHeader('Content-Disposition','attachment; filename="exportacao-'.$id.'.json"');
        $this->assertSame(['matricula'=>'C2-1'],$r->json('dados'));
        $antes=$this->eventoCount();$this->chamar($token,'HEAD',$base.'/arquivo')->assertStatus(405);
        $this->assertSame($antes,$this->eventoCount());
    }
}
