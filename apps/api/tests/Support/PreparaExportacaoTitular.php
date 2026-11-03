<?php
namespace Tests\Support;
use App\Models\Usuario;
use App\Services\{AutorizacoesExportacaoTitular,PedidosTitulares};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
trait PreparaExportacaoTitular
{
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
}
