<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\{CatalogoPermissoes, ElegibilidadeCopia};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ElegibilidadeCopiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo'=>true, 'espacos.pessoal'=>true, 'vinculos.obrigatorio'=>true, 'copias.ativo'=>true]);
        Usuario::forceCreate(['id'=>1, 'perfil'=>'admin_rede', 'nome'=>'Pessoa fictícia',
            'email'=>'t5@example.test', 'cpf'=>'00000000001', 'password'=>'ficticio', 'ativo'=>true]);
        DB::table('clientes')->insert(['id'=>1, 'tipo'=>'rede', 'nome'=>'Cliente', 'modalidade'=>'interno', 'situacao'=>'ativo']);
        foreach ([1, 2] as $id) {
            DB::table('redes')->insert(['id'=>$id, 'nome'=>'Rede', 'tipo'=>'estadual', 'uf'=>'BA']);
            DB::table('nucleos')->insert(['id'=>$id, 'rede_id'=>$id, 'nome'=>'Núcleo']);
            DB::table('escolas')->insert(['id'=>$id, 'nucleo_id'=>$id, 'nome'=>'Escola', 'inep'=>sprintf('%08d', $id), 'tipo_rede'=>'estadual']);
            foreach (['rede'=>1, 'nucleo'=>2, 'escola'=>3] as $tipo=>$offset) {
                DB::table('espacos')->insert(['id'=>($id-1)*3+$offset, 'cliente_id'=>1, 'tipo'=>$tipo,
                    'nome'=>$tipo, 'situacao'=>'ativo', $tipo.'_id'=>$id]);
            }
        }
        DB::table('espacos')->insert(['id'=>7, 'cliente_id'=>1, 'tipo'=>'pessoal', 'nome'=>'Pessoal',
            'situacao'=>'ativo', 'usuario_proprietario_id'=>1]);
        foreach ([[1,1,'admin_rede'],[2,3,'dir_escolar'],[3,7,'professor_autonomo'],[4,3,'professor'],
            [5,2,'dir_nucleo'],[6,3,'coordenador'],[7,4,'admin_rede']] as [$id,$espaco,$perfil]) {
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>1,'espaco_id'=>$espaco,'perfil'=>$perfil,
                'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
    }

    private function ator(int $vinculo): Usuario { return Usuario::findOrFail(1)->noVinculo($vinculo); }

    private function negado(callable $acao): void
    {
        try { $acao(); $this->fail('A avaliação deveria ser recusada.'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
    }

    public function test_contextos_da_mesma_pessoa_sao_separados_em_ambas_as_direcoes(): void
    {
        $resultado = ElegibilidadeCopia::avaliar($this->ator(1), $this->ator(3), 3, 7);
        $this->assertSame(1, $resultado['origem']['vinculo_id']);
        $this->assertSame(3, $resultado['destino']['vinculo_id']);
        $this->assertNotSame($resultado['origem']['versao_acesso'], $resultado['destino']['versao_acesso']);
        $this->assertSame(2, ElegibilidadeCopia::avaliar($this->ator(3), $this->ator(2), 7, 3)['destino']['vinculo_id']);
        $this->negado(fn()=>ElegibilidadeCopia::avaliar($this->ator(1), $this->ator(1), 3, 7));
        $this->negado(fn()=>ElegibilidadeCopia::avaliar(Usuario::find(1), $this->ator(3), 3, 7));
    }

    public function test_rede_nao_alcanca_outra_rede_mesmo_com_cliente_e_conta_iguais(): void
    {
        $this->negado(fn()=>ElegibilidadeCopia::avaliar($this->ator(1), $this->ator(3), 6, 7));
        $this->assertSame(6, ElegibilidadeCopia::avaliar($this->ator(7), $this->ator(3), 6, 7)['origem']['espaco_id']);
        $this->negado(fn()=>ElegibilidadeCopia::avaliar($this->ator(2), $this->ator(3), 6, 7));
    }

    public function test_outros_perfis_nao_autorizam_e_nao_recebem_capacidades_extras(): void
    {
        foreach ([4,5,6] as $vinculo) {
            $this->negado(fn()=>ElegibilidadeCopia::avaliar($this->ator($vinculo), $this->ator(3), 3, 7));
        }
        foreach (CatalogoPermissoes::porPerfil() as $perfil=>$grupos) {
            foreach (['copia.autorizar_origem','copia.autorizar_destino'] as $capacidade) {
                $this->assertSame(in_array($perfil,['admin_rede','dir_escolar','professor_autonomo'],true), in_array($capacidade,$grupos['padrao'],true));
                $this->assertNotContains($capacidade, $grupos['extra']);
            }
        }
    }

    public function test_retirada_da_permissao_em_qualquer_lado_bloqueia(): void
    {
        foreach ([[1,'copia.autorizar_origem'],[3,'copia.autorizar_destino']] as [$vinculo,$capacidade]) {
            DB::table('vinculo_permissoes')->insert(['vinculo_id'=>$vinculo,'permissao'=>$capacidade,'efeito'=>'retirar']);
            $this->negado(fn()=>ElegibilidadeCopia::avaliar($this->ator(1),$this->ator(3),3,7));
            DB::table('vinculo_permissoes')->where('vinculo_id',$vinculo)->delete();
        }
    }

    public function test_contextos_internos_nao_herdam_os_vinculos_da_conta(): void
    {
        config(['acessos_internos.ativo'=>true]);
        foreach (['desenvolvedor','suporte','financeiro'] as $i=>$perfil) {
            DB::table('usuario_acessos_internos')->insert(['id'=>$i+1,'usuario_id'=>1,'perfil'=>$perfil,
                'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
            $interno=Usuario::find(1)->noAcessoInterno($i+1,1);
            $this->negado(fn()=>ElegibilidadeCopia::avaliar($interno,$this->ator(3),3,7));
            $this->negado(fn()=>ElegibilidadeCopia::avaliar($this->ator(1),$interno,3,7));
        }
    }

    public function test_proprietario_nao_autoriza_espaco_pessoal_alheio(): void
    {
        Usuario::forceCreate(['id'=>2,'perfil'=>'professor','nome'=>'Outra pessoa','email'=>'t5-outra@example.test',
            'cpf'=>'00000000002','password'=>'ficticio','ativo'=>true]);
        DB::table('espacos')->insert(['id'=>8,'cliente_id'=>1,'tipo'=>'pessoal','nome'=>'Outro pessoal',
            'situacao'=>'ativo','usuario_proprietario_id'=>2]);
        $this->negado(fn()=>ElegibilidadeCopia::avaliar($this->ator(1),$this->ator(3),3,8));
        $this->negado(fn()=>ElegibilidadeCopia::avaliar($this->ator(3),$this->ator(2),8,3));
    }

    public function test_objeto_previamente_validado_nao_conserva_vinculo_revogado_ou_expirado(): void
    {
        $origem=$this->ator(1); $destino=$this->ator(3);
        ElegibilidadeCopia::avaliar($origem,$destino,3,7);
        DB::table('usuario_vinculos')->where('id',1)->update(['fim_em'=>now()->subMinute()]);
        $this->negado(fn()=>ElegibilidadeCopia::avaliar($origem,$destino,3,7));
        DB::table('usuario_vinculos')->where('id',1)->update(['fim_em'=>null]);
        DB::table('usuario_vinculos')->where('id',3)->update(['situacao'=>'encerrado']);
        $this->negado(fn()=>ElegibilidadeCopia::avaliar($origem,$destino,3,7));
    }

    public function test_suspensao_da_hierarquia_e_cliente_e_usuario_bloqueia(): void
    {
        $origem=$this->ator(1); $destino=$this->ator(3);
        foreach ([1,2,3,7] as $espaco) {
            DB::table('espacos')->where('id',$espaco)->update(['situacao'=>'suspenso']);
            $this->negado(fn()=>ElegibilidadeCopia::avaliar($origem,$destino,3,7));
            DB::table('espacos')->where('id',$espaco)->update(['situacao'=>'ativo']);
        }
        DB::table('clientes')->where('id',1)->update(['situacao'=>'suspenso']);
        $this->negado(fn()=>ElegibilidadeCopia::avaliar($origem,$destino,3,7));
        DB::table('clientes')->where('id',1)->update(['situacao'=>'ativo']);
        DB::table('usuarios')->where('id',1)->update(['ativo'=>false]);
        $this->negado(fn()=>ElegibilidadeCopia::avaliar($origem,$destino,3,7));
    }

    public function test_flag_e_destinos_invalidos_falham_fechados(): void
    {
        foreach ([[3,3],[1,7],[3,2],[999,7],[3,999]] as [$origem,$destino]) {
            $this->negado(fn()=>ElegibilidadeCopia::avaliar($this->ator(1),$this->ator(3),$origem,$destino));
        }
        config(['copias.ativo'=>false]);
        $this->negado(fn()=>ElegibilidadeCopia::avaliar($this->ator(1),$this->ator(3),3,7));
    }
}
