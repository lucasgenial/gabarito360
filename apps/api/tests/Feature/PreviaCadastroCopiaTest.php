<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\PreviaCadastroCopia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PreviaCadastroCopiaTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'copias.ativo'=>true]);
        foreach ([1,2] as $id) Usuario::forceCreate(['id'=>$id,'perfil'=>'professor','nome'=>'Fictício',
            'email'=>"c1-$id@example.test",'cpf'=>sprintf('%011d',$id),'password'=>'ficticio','ativo'=>true]);
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'rede','nome'=>'Rede','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('clientes')->insert(['id'=>2,'tipo'=>'professor','nome'=>'Pessoal','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('redes')->insert(['id'=>1,'nome'=>'Rede','tipo'=>'estadual','uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>1,'rede_id'=>1,'nome'=>'Núcleo']);
        DB::table('escolas')->insert(['id'=>1,'nucleo_id'=>1,'nome'=>'Escola','inep'=>'00000001','tipo_rede'=>'estadual']);
        foreach (['rede'=>1,'nucleo'=>2,'escola'=>3] as $tipo=>$id) DB::table('espacos')->insert([
            'id'=>$id,'cliente_id'=>1,'tipo'=>$tipo,'nome'=>$tipo,'situacao'=>'ativo',$tipo.'_id'=>1]);
        DB::table('espacos')->insert(['id'=>4,'cliente_id'=>2,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>2]);
        foreach ([[1,1,3,'dir_escolar'],[2,2,4,'professor_autonomo'],[3,1,3,'professor']] as [$id,$u,$e,$perfil]) {
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$u,'espaco_id'=>$e,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>'2025-01-01','versao_autorizacao'=>1]);
        }
        foreach ([1,2,3] as $id) {
            $pessoal=$id===3;
            DB::table('turmas')->insert(['id'=>$id,'escola_id'=>$pessoal?null:1,'espaco_id'=>$pessoal?4:3,
                'espaco_tipo'=>$pessoal?'pessoal':'escola','nome'=>'Turma fictícia','serie'=>'3','turno'=>'manha','ano_letivo'=>2026]);
            DB::table('alunos')->insert(['id'=>$id,'turma_id'=>$id,'espaco_id'=>$pessoal?4:3,'espaco_tipo'=>$pessoal?'pessoal':'escola',
                'nome'=>'Aluno fictício','matricula'=>"MAT-$id",'avatar_id'=>5,'data_nascimento'=>$pessoal?null:'2010-01-01',
                'genero'=>$pessoal?null:'O','nome_responsavel'=>$pessoal?null:'Responsável fictício','foto_path'=>$pessoal?null:'privado/ficticio.jpg']);
        }
    }
    private function ator(int $v=1): Usuario { return Usuario::findOrFail($v===2?2:1)->noVinculo($v); }
    private function previa(string $tipo='pessoal', array $turmas=[1], array $alunos=[1]): array
    { return (new PreviaCadastroCopia)->previaOrigem($this->ator(),3,$tipo,$turmas,$alunos); }
    private function recusa(int $status, callable $f): void
    {
        try { $f(); $this->fail('Deveria recusar.'); }
        catch (HttpException $e) { $this->assertSame($status,$e->getStatusCode()); }
    }
    public function test_pessoal_recebe_somente_projecao_minima_e_nao_grava_nem_inclui_dependentes(): void
    {
        $sql=[]; DB::listen(function ($q) use (&$sql) { $sql[]=$q->sql; });
        $r=$this->previa();
        $this->assertCount(1,$r['turmas']); $this->assertCount(1,$r['alunos']);
        $this->assertSame(['nome','matricula','avatar_id','ativo'],array_keys($r['alunos'][0]['dados']));
        $this->assertSame(['nome','serie','turno','ano_letivo','ativo'],array_keys($r['turmas'][0]['dados']));
        $this->assertFalse($r['autoriza_execucao']); $this->assertTrue($r['evidencia_identificados_obrigatoria']);
        foreach (['cpf','cpf_hash','data_nascimento','genero','nome_responsavel','foto_path','responsavel','token'] as $campo) {
            $this->assertStringNotContainsString('"'.$campo.'"',json_encode($r));
        }
        foreach ($sql as $query) $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter)\b/i',$query);
        $this->assertDatabaseCount('alunos',3); $this->assertDatabaseCount('copias_lotes',0);
        $this->assertSame([],$this->previa('pessoal',[1],[])['alunos']);
    }
    public function test_escolar_indica_nascimento_faltante_sem_inventar_e_preserva_valor_valido(): void
    {
        $r=(new PreviaCadastroCopia)->previaOrigem($this->ator(2),4,'escola',[3],[3]);
        $this->assertSame([['entidade'=>'aluno','origem_id'=>3,'codigo'=>'campo_obrigatorio_ausente','campo'=>'data_nascimento']],$r['pendencias']);
        $this->assertArrayNotHasKey('data_nascimento',$r['alunos'][0]['dados']);
        $escolar=$this->previa('escola');
        $this->assertSame('2010-01-01',$escolar['alunos'][0]['dados']['data_nascimento']);
        $this->assertSame([],$escolar['pendencias']); $this->assertFalse($escolar['autoriza_execucao']);
    }
    public function test_selecao_explicita_limites_ids_e_turma_obrigatoria(): void
    {
        foreach ([[[],[]],[[1,1],[]],[['1'],[]],[range(1,101),[]],[[0],[]],[[1],[1,1]],[[1],[2]]] as [$t,$a]) {
            $this->recusa(422,fn()=>$this->previa('pessoal',$t,$a));
        }
        $this->recusa(404,fn()=>$this->previa('pessoal',[3],[]));
        $this->recusa(404,fn()=>$this->previa('pessoal',[1],[3]));
        $this->recusa(404,fn()=>$this->previa('pessoal',[999],[]));
        $this->recusa(422,fn()=>$this->previa('rede'));
    }
    public function test_contexto_errado_perfil_sem_autorizacao_e_revogacao_bloqueiam(): void
    {
        $s=new PreviaCadastroCopia;
        $this->recusa(403,fn()=>$s->previaOrigem($this->ator(2),3,'pessoal',[1],[1]));
        $this->recusa(403,fn()=>$s->previaOrigem($this->ator(3),3,'pessoal',[1],[1]));
        $this->recusa(403,fn()=>$s->previaOrigem(Usuario::find(1),3,'pessoal',[1],[1]));
        $this->previa();
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'encerrado']);
        $this->recusa(403,fn()=>$this->previa());
    }
    public function test_flags_regime_e_suspensao_bloqueiam(): void
    {
        foreach (['copias.ativo','espacos.ativo','vinculos.obrigatorio'] as $flag) {
            config([$flag=>false]); $this->recusa(403,fn()=>$this->previa()); config([$flag=>true]);
        }
        DB::table('espacos')->where('id',3)->update(['situacao'=>'suspenso']);
        $this->recusa(403,fn()=>$this->previa());
    }
    public function test_digest_estavel_por_selecao_e_muda_com_conteudo_destino_ou_versao(): void
    {
        $r=$this->previa('pessoal',[2,1],[2,1]);
        $this->assertSame($r['conteudo_digest'],$this->previa('pessoal',[1,2],[1,2])['conteudo_digest']);
        $this->assertNotSame($r['conteudo_digest'],$this->previa('escola',[1,2],[1,2])['conteudo_digest']);
        DB::table('alunos')->where('id',1)->update(['nome'=>'Nome fictício alterado']);
        $novo=$this->previa('pessoal',[1,2],[1,2]); $this->assertNotSame($r['conteudo_digest'],$novo['conteudo_digest']);
        DB::table('usuario_vinculos')->where('id',1)->increment('versao_autorizacao');
        $this->assertNotSame($novo['conteudo_digest'],$this->previa('pessoal',[1,2],[1,2])['conteudo_digest']);
    }
    public function test_avatar_fora_do_catalogo_e_texto_vazio_nao_sao_corrigidos_silenciosamente(): void
    {
        DB::table('alunos')->where('id',1)->update(['avatar_id'=>37]);
        $this->recusa(422,fn()=>$this->previa());
        DB::table('alunos')->where('id',1)->update(['avatar_id'=>null]);
        DB::table('turmas')->where('id',1)->update(['nome'=>'']);
        $this->recusa(422,fn()=>$this->previa());
    }

    public function test_nascimento_invalido_legado_nao_se_torna_dado_escolar_valido(): void
    {
        if (DB::connection()->getDriverName()!=='sqlite') $this->markTestSkipped('MariaDB já recusa data inválida na escrita.');
        DB::table('alunos')->where('id',1)->update(['data_nascimento'=>'2010-02-31']);
        $this->recusa(422,fn()=>$this->previa('escola'));
        $this->assertArrayNotHasKey('data_nascimento',$this->previa('pessoal')['alunos'][0]['dados']);
    }
}
