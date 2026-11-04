<?php
namespace Tests\Support;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Support\Facades\DB;

trait CenarioDisciplinas
{
    protected function setUp(): void
    {
        parent::setUp(); config(['espacos.ativo' => true, 'disciplinas.ativo' => true]);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-04 15:00:00', 'UTC')); Permissao::limparCache();
        foreach ([1, 2, 3] as $id) Usuario::forceCreate(['id' => $id, 'perfil' => 'professor', 'nome' => 'Fictício '.$id, 'email' => $id.'@example.test', 'cpf' => str_pad((string) $id, 11, '0', STR_PAD_LEFT), 'password' => 'ficticio', 'ativo' => true]);
        foreach ([1, 2] as $id) {
            DB::table('clientes')->insert(['id' => $id, 'tipo' => 'rede', 'nome' => 'Cliente', 'modalidade' => 'interno', 'situacao' => 'ativo']);
            DB::table('redes')->insert(['id' => $id, 'nome' => 'Rede', 'tipo' => 'estadual', 'uf' => 'BA']);
            DB::table('nucleos')->insert(['id' => $id, 'rede_id' => $id, 'nome' => 'Núcleo']);
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => $id, 'nome' => 'Escola', 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            foreach (['rede' => 0, 'nucleo' => 2, 'escola' => 4] as $tipo => $offset) DB::table('espacos')->insert(['id' => $id + $offset, 'cliente_id' => $id, 'tipo' => $tipo, 'nome' => 'Espaço', 'situacao' => 'ativo', $tipo.'_id' => $id]);
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'nome' => '1A', 'serie' => '1', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            DB::table('provas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'criado_por' => 1, 'titulo' => 'Legado', 'disciplina' => 'Matematica antiga', 'serie' => '1', 'num_questoes' => 1, 'num_alternativas' => 4]);
        }
        foreach ([[1, 1, 5, 'coordenador'], [2, 1, 6, 'coordenador'], [3, 2, 5, 'professor'], [4, 1, 5, 'dir_escolar'], [5, 3, 5, 'professor']] as [$id, $usuario, $espaco, $perfil])
            DB::table('usuario_vinculos')->insert(['id' => $id, 'usuario_id' => $usuario, 'espaco_id' => $espaco, 'perfil' => $perfil, 'situacao' => 'ativo', 'inicio_em' => now()->subDay(), 'versao_autorizacao' => 1]);
        DB::table('vinculo_turmas')->insert(['vinculo_id' => 3, 'espaco_id' => 5, 'turma_id' => 1]);
        app('auth')->forgetGuards();
        \Laravel\Sanctum\Sanctum::actingAs(Usuario::findOrFail(1)->noVinculo(1));
    }

}
