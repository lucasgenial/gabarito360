<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\ConsultarCatalogoInstituicoes;
use App\Services\PrepararCatalogoInstituicoes;
use App\Services\PublicarCatalogoInstituicoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\ArquivosCatalogo;
use Tests\TestCase;

class ConciliarCatalogoTest extends TestCase
{
    use RefreshDatabase, ArquivosCatalogo;
    protected function tearDown(): void { $this->limparArquivosCatalogo(); parent::tearDown(); }

    public function test_relatorio_preserva_escolas_usuarios_e_gabarito_existente_sem_associar(): void
    {
        DB::table('redes')->insert(['id'=>1,'nome'=>'Rede fictícia','tipo'=>'estadual','uf'=>'RO']);
        DB::table('nucleos')->insert(['id'=>1,'rede_id'=>1,'nome'=>'Núcleo fictício']);
        foreach ([1=>['11000001','RO','1100001'],2=>['12000001','BA','1200001'],3=>['13000001',null,null],4=>['99999999','BA','2900001']] as $id=>$dados) {
            DB::table('escolas')->insert(['id'=>$id,'nucleo_id'=>1,'nome'=>'Nome manual '.$id,'inep'=>$dados[0],'uf'=>$dados[1],'co_municipio'=>$dados[2],'tipo_rede'=>'estadual']);
        }
        Usuario::forceCreate(['id'=>1,'nome'=>'Professor fictício','perfil'=>'professor','email'=>'catalogo@example.test','cpf'=>'00000000001','password'=>'ficticio']);
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'professor','nome'=>'Cliente fictício','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>1,'cliente_id'=>1,'tipo'=>'pessoal','nome'=>'Pessoal fictício','situacao'=>'ativo','usuario_proprietario_id'=>1]);
        DB::table('provas')->insert(['id'=>1,'escola_id'=>null,'espaco_id'=>1,'espaco_tipo'=>'pessoal','criado_por'=>1,'titulo'=>'Prova preservada','disciplina'=>'Matemática','serie'=>'1','num_questoes'=>1,'num_alternativas'=>5]);
        DB::table('gabaritos')->insert(['id'=>1,'prova_id'=>1,'espaco_id'=>1]);
        DB::table('gabarito_questoes')->insert(['gabarito_id'=>1,'espaco_id'=>1,'numero_questao'=>1,'alternativa'=>'B','peso'=>2.5]);
        $antes = [];
        foreach (['escolas','usuarios','usuario_vinculos','espacos','provas','gabaritos','gabarito_questoes','turmas','alunos','cartoes','cartao_respostas','notas'] as $t) $antes[$t] = DB::table($t)->get()->toJson();
        $entrada = $this->fonteCatalogo(); $i = app(PrepararCatalogoInstituicoes::class)->executar(...$entrada);
        app(PublicarCatalogoInstituicoes::class)->executar($i['importacao_id'],$entrada['ufs']);
        $r = app(ConsultarCatalogoInstituicoes::class)->conciliacao();
        self::assertSame(['candidato_compativel','geografia_divergente','dados_incompletos','sem_correspondencia'],array_column($r,'situacao'));
        self::assertTrue($r[0]['nome_divergente']);
        foreach ($r as $linha) self::assertFalse($linha['associacao_executada']);
        foreach ($antes as $t=>$json) self::assertSame($json,DB::table($t)->get()->toJson(),$t);
        $this->artisan('g360:catalogo-conciliar')->expectsOutputToContain('relatorio_sem_associacao')->assertSuccessful();
        $this->assertDatabaseHas('gabarito_questoes',['gabarito_id'=>1,'alternativa'=>'B','peso'=>2.5]);
    }
}
