<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class F2DashboardLotesTest extends TestCase
{
    use RefreshDatabase, \Tests\Support\CenarioDisciplinas {
        setUp as private prepararBase;
    }

    protected function setUp(): void
    {
        $this->prepararBase();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-06 12:00:00', 'UTC'));
        config(['vinculos.obrigatorio' => true, 'disciplinas.ativo' => false]);
        $this->usuario(4, 'aplicador');
        $this->usuario(5, 'aluno');
        $this->usuario(6, 'secretario_educacao');
        foreach ([[6, 4, 'aplicador'], [7, 5, 'aluno']] as [$id, $usuario, $perfil]) {
            DB::table('usuario_vinculos')->insert(['id' => $id, 'usuario_id' => $usuario, 'espaco_id' => 5,
                'perfil' => $perfil, 'situacao' => 'ativo', 'inicio_em' => now()->subDay(), 'versao_autorizacao' => 1]);
        }
        $this->turma(3);
        $this->aluno(1);
        $this->aluno(2, ativo: false);
        $this->aluno(3, turma: 3);
        $this->prova(1);
        $this->nota(1, 1, 6);
        DB::table('vinculo_alunos')->insert(['vinculo_id' => 7, 'aluno_id' => 1, 'espaco_id' => 5]);
    }

    private function usuario(int $id, string $perfil): void
    {
        Usuario::forceCreate(['id' => $id, 'nome' => 'Conta fictícia '.$id, 'perfil' => $perfil,
            'email' => 'f2-'.$id.'@example.test', 'cpf' => str_pad((string) $id, 11, '0', STR_PAD_LEFT),
            'password' => 'ficticio', 'ativo' => true]);
    }

    private function ator(int $usuario, ?int $vinculo): void
    {
        Permissao::limparCache();
        app('auth')->forgetGuards();
        $u = Usuario::findOrFail($usuario);
        Sanctum::actingAs($vinculo === null ? $u : $u->noVinculo($vinculo));
    }

    private function turma(int $id, int $escola = 1, int $espaco = 5): void
    {
        DB::table('turmas')->insert(['id' => $id, 'escola_id' => $escola, 'espaco_id' => $espaco,
            'nome' => 'Turma '.str_pad((string) $id, 3, '0', STR_PAD_LEFT), 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
    }

    private function aluno(int $id, int $turma = 1, bool $ativo = true, int $espaco = 5): void
    {
        DB::table('alunos')->insert(['id' => $id, 'turma_id' => $turma, 'espaco_id' => $espaco,
            'nome' => 'Aluno fictício '.$id, 'matricula' => 'F2-'.$id, 'ativo' => $ativo]);
    }

    private function prova(int $id, int $turma = 1, ?string $data = '2026-11-06', string $disciplina = 'Matemática', int $escola = 1, int $espaco = 5): void
    {
        DB::table('provas')->updateOrInsert(['id' => $id], ['escola_id' => $escola, 'espaco_id' => $espaco,
            'criado_por' => 2, 'perfil_criacao' => 'professor', 'titulo' => 'Avaliação fictícia '.$id, 'disciplina' => $disciplina,
            'serie' => '3', 'num_questoes' => 10, 'num_alternativas' => 4, 'nota_maxima' => 10,
            'status' => 'publicada', 'data_aplicacao' => $data, 'updated_at' => now()->addMinutes($id)]);
        DB::table('prova_turmas')->insert(['prova_id' => $id, 'turma_id' => $turma, 'espaco_id' => $espaco]);
    }

    private function nota(int $prova, int $aluno, float $valor, int $turma = 1, int $espaco = 5): void
    {
        $cartao = DB::table('cartoes')->insertGetId(['prova_id' => $prova, 'aluno_id' => $aluno,
            'turma_aplicacao_id' => $turma, 'espaco_id' => $espaco, 'status' => 'lido']);
        DB::table('notas')->insert(['cartao_id' => $cartao, 'prova_id' => $prova, 'aluno_id' => $aluno,
            'turma_id' => $turma, 'espaco_id' => $espaco, 'acertos' => (int) $valor, 'total_questoes' => 10,
            'nota_final' => $valor, 'status_aprovacao' => $valor >= 6 ? 'aprovado' : 'recuperacao']);
    }

    private function medir(string $rota): array
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $r = $this->getJson('/api/v1/dashboard/'.$rota)->assertOk();
            return [count(DB::getQueryLog()), $r];
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public static function paineisProvas(): array
    {
        return [['professor', 2, 3, 'provas'], ['aplicador', 4, 6, 'provas_hoje']];
    }

    #[DataProvider('paineisProvas')]
    public function test_uma_quatro_oito_provas_e_260_alunos_nao_acrescentam_sql_por_prova(string $rota, int $usuario, int $vinculo, string $campo): void
    {
        $this->ator($usuario, $vinculo);
        [$uma] = $this->medir($rota);
        foreach (range(10, 12) as $id) $this->prova($id);
        [$quatro] = $this->medir($rota);
        foreach (range(13, 16) as $id) $this->prova($id);
        foreach (range(10, 269) as $id) $this->aluno($id);
        [$oito, $r] = $this->medir($rota);
        $r->assertJsonCount(8, 'data.'.$campo);
        $this->assertLessThanOrEqual($uma + 2, $quatro, 'Quatro provas não devem disparar consultas para cada prova.');
        $this->assertLessThanOrEqual($uma + 2, $oito, 'Oito provas e 260 cadastros não devem aumentar consultas por registro.');
        foreach ($r->json('data.'.$campo) as $prova) $this->assertSame(261, $prova['total_alunos']);
    }

    public function test_professor_preserva_campos_ordenacao_turmas_autorizadas_e_contagem_de_cartoes(): void
    {
        DB::table('prova_turmas')->insert(['prova_id' => 1, 'turma_id' => 3, 'espaco_id' => 5]);
        $this->nota(1, 3, 0, turma: 3);
        $this->prova(10, data: '2026-11-08');
        $this->prova(11, data: '2026-11-09');
        DB::table('provas')->where('id', 10)->update(['status' => 'em_correcao']);
        DB::table('cartoes')->insert(['prova_id' => 1, 'espaco_id' => 5, 'status' => 'pendente']);
        $this->ator(2, 3);
        $r = $this->getJson('/api/v1/dashboard/professor')->assertOk()->assertJsonCount(3, 'data.provas');
        $this->assertSame([10, 11, 1], array_column($r->json('data.provas'), 'id'));
        $p = collect($r->json('data.provas'))->firstWhere('id', 1);
        $this->assertEqualsCanonicalizing(['id','titulo','disciplina','turma','turmas_lista','data_aplicacao','total_alunos','cartoes_lidos','status'], array_keys($p));
        $this->assertSame(1, $p['total_alunos']);
        $this->assertSame(1, $p['cartoes_lidos']);
        $this->assertSame([['id' => 1, 'nome' => '1A']], $p['turmas_lista']);
        $this->assertSame('1A', $p['turma']);
        $this->assertSame('2026-11-06T00:00:00.000000Z', $p['data_aplicacao']);
        DB::table('prova_turmas')->where('prova_id', 11)->delete();
        $p = collect($this->getJson('/api/v1/dashboard/professor')->assertOk()->json('data.provas'))->firstWhere('id', 11);
        $this->assertSame('—', $p['turma']);
        $this->assertSame([], $p['turmas_lista']);
        $this->assertSame(0, $p['total_alunos']);
        $this->assertSame(0, $p['cartoes_lidos']);
    }

    public function test_diretor_preserva_media_historica_zero_nulo_ultima_prova_e_primeiro_docente(): void
    {
        DB::table('notas')->where('prova_id', 1)->update(['nota_final' => 0]);
        $this->nota(1, 3, 8); // Aluno agora na turma 3; resultado pertence à aplicação na turma 1.
        $this->prova(10, data: '2026-11-08', disciplina: 'Física');
        DB::table('vinculo_turmas')->insert(['vinculo_id' => 5, 'turma_id' => 1, 'espaco_id' => 5]);
        $this->ator(1, 4);
        $r = $this->getJson('/api/v1/dashboard/dir-escolar')->assertOk();
        $turma = collect($r->json('data.turmas'))->firstWhere('id', 1);
        $this->assertSame(1, $turma['total_alunos']);
        $this->assertSame(4, $turma['media']);
        $this->assertSame('plano_apoio', $turma['status']);
        $this->assertSame('Física', $turma['ultima_prova']);
        $this->assertSame('Fictício 2', $turma['professor']);
        $vazia = collect($r->json('data.turmas'))->firstWhere('id', 3);
        $this->assertNull($vazia['media']);
        $this->assertNull($vazia['ultima_prova']);
        $this->assertNull($vazia['professor']);
        $this->assertSame('sem_dados', $vazia['status']);
        DB::table('notas')->where('prova_id', 1)->update(['nota_final' => 0]);
        $turma = collect($this->getJson('/api/v1/dashboard/dir-escolar')->assertOk()->json('data.turmas'))->firstWhere('id', 1);
        $decimal = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
        $this->assertSame($decimal ? 0 : null, $turma['media']); // Oráculo do controller anterior: DECIMAL zero era truthy.
    }

    public function test_uma_quatro_oito_turmas_nao_acrescentam_consultas_por_turma(): void
    {
        DB::table('turmas')->where('id', 3)->update(['ativo' => false]);
        $this->ator(1, 4);
        [$uma] = $this->medir('dir-escolar');
        foreach (range(10, 12) as $id) $this->turma($id);
        [$quatro] = $this->medir('dir-escolar');
        foreach (range(13, 16) as $id) {
            $this->turma($id);
            $this->aluno($id, turma: $id);
        }
        [$oito, $r] = $this->medir('dir-escolar');
        $r->assertJsonCount(8, 'data.turmas');
        $this->assertLessThanOrEqual($uma + 2, $quatro);
        $this->assertLessThanOrEqual($uma + 2, $oito);
    }

    public function test_aluno_preserva_ultima_nota_media_tendencia_e_periodos_inclusive_zero_e_nulos(): void
    {
        $this->prova(10, data: '2026-11-08'); $this->nota(10, 1, 8);
        $this->prova(11, data: '2026-03-31', disciplina: 'Ciências'); $this->nota(11, 1, 0);
        $this->prova(12, data: '2026-04-01', disciplina: 'História'); $this->nota(12, 1, 5);
        $this->prova(13, data: null, disciplina: 'Artes'); $this->nota(13, 1, 9);
        $this->ator(5, 7);
        $r = $this->getJson('/api/v1/dashboard/aluno')->assertOk();
        $notas = collect($r->json('data.notas'))->keyBy('disciplina');
        $decimal = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
        $this->assertSame('2026-11-08', $notas['Matemática']['ultima_data']);
        // Projeções SQL diretas preservam a representação decimal do driver, como antes do lote.
        $this->assertSame($decimal ? '7.0' : 7, $notas['Matemática']['media_bimestral']);
        $this->assertSame($decimal ? '8.00' : 8, $notas['Matemática']['ultima_nota']);
        $this->assertSame('up', $notas['Matemática']['tendencia']);
        $this->assertSame($decimal ? '0.00' : 0, $notas['Ciências']['ultima_nota']);
        $this->assertSame('flat', $notas['Ciências']['tendencia']);
        $this->assertNull($notas['Artes']['ultima_data']);
        $this->assertSame($decimal ? '9.00' : 9, $notas['Artes']['ultima_nota']);
        $this->assertSame([
            ['bimestre' => 1, 'label' => '1º bim.', 'media' => $decimal ? 0 : null],
            ['bimestre' => 2, 'label' => '2º bim.', 'media' => 5],
            ['bimestre' => 3, 'label' => '3º bim.', 'media' => null],
            ['bimestre' => 4, 'label' => '4º bim.', 'media' => 7],
        ], $r->json('data.bimestres'));
    }

    public function test_nucleo_preserva_zero_decimal_nos_quatro_periodos_sem_confundir_ausencia(): void
    {
        $this->usuario(7, 'dir_nucleo');
        DB::table('usuario_vinculos')->insert(['id' => 8, 'usuario_id' => 7, 'espaco_id' => 3, 'perfil' => 'dir_nucleo',
            'situacao' => 'ativo', 'inicio_em' => now()->subDay(), 'versao_autorizacao' => 1]);
        DB::table('notas')->where('prova_id', 1)->update(['nota_final' => 0, 'acertos' => 0]);
        foreach ([10 => '2026-03-31', 11 => '2026-04-01', 12 => '2026-07-01'] as $id => $data) {
            $this->prova($id, data: $data); $this->nota($id, 1, 0);
        }
        $this->ator(7, 8);
        $decimal = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
        $zero = $decimal ? 0 : null;
        $r = $this->getJson('/api/v1/dashboard/dir-nucleo')->assertOk();
        $this->assertSame([$zero, $zero, $zero, $zero], array_column($r->json('data.bimestres'), 'media'));
        DB::table('notas')->delete();
        $r = $this->getJson('/api/v1/dashboard/dir-nucleo')->assertOk();
        $this->assertSame([null, null, null, null], array_column($r->json('data.bimestres'), 'media'));
    }

    public function test_nucleo_comparativo_com_multiplas_turmas_preserva_contagens_media_zero_e_nulo(): void
    {
        $this->usuario(7, 'dir_nucleo');
        DB::table('usuario_vinculos')->insert(['id' => 8, 'usuario_id' => 7, 'espaco_id' => 3, 'perfil' => 'dir_nucleo',
            'situacao' => 'ativo', 'inicio_em' => now()->subDay(), 'versao_autorizacao' => 1]);
        $this->turma(4);
        $this->aluno(4, turma: 4); $this->aluno(5); $this->aluno(6, turma: 3, ativo: false);
        foreach ([[10, 3, 3, 0], [11, 4, 4, 9], [12, 1, 5, 3]] as [$prova, $turma, $aluno, $nota]) {
            $this->prova($prova, turma: $turma); $this->nota($prova, $aluno, $nota, turma: $turma);
        }
        DB::table('escolas')->insert(['id' => 3, 'nucleo_id' => 1, 'nome' => 'Escola sem resultados fictícia', 'inep' => '00000003', 'tipo_rede' => 'estadual']);
        DB::table('espacos')->insert(['id' => 7, 'cliente_id' => 1, 'tipo' => 'escola', 'escola_id' => 3, 'nome' => 'Espaço fictício', 'situacao' => 'ativo']);
        $this->turma(7, escola: 3, espaco: 7);
        $this->ator(7, 8);
        $maria = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
        $depth = $maria ? (int) DB::selectOne('SELECT @@SESSION.optimizer_search_depth as valor')->valor : null;
        try {
            // Mesmo otimizador limitado da homologação; só a sessão do banco descartável do teste.
            if ($maria) DB::statement('SET SESSION optimizer_search_depth = 4');
            $r = $this->getJson('/api/v1/dashboard/dir-nucleo')->assertOk()->assertJsonCount(2, 'data.escolas');
            $escolas = collect($r->json('data.escolas'))->keyBy('id');
            $this->assertSame(3, $escolas[1]['total_turmas']);
            $this->assertSame(4, $escolas[1]['total_alunos']);
            $this->assertSame($maria ? '4.5' : 4.5, $escolas[1]['media']);
            $this->assertSame('abaixo_meta', $escolas[1]['status']);
            $this->assertSame(1, $escolas[3]['total_turmas']);
            $this->assertSame(0, $escolas[3]['total_alunos']);
            $this->assertNull($escolas[3]['media']);
            $this->assertSame('sem_dados', $escolas[3]['status']);
            DB::table('notas')->update(['nota_final' => 0, 'acertos' => 0]);
            $escolas = collect($this->getJson('/api/v1/dashboard/dir-nucleo')->assertOk()->json('data.escolas'))->keyBy('id');
            $this->assertSame(4, $escolas[1]['total_alunos']);
            $this->assertSame($maria ? '0.0' : 0, $escolas[1]['media']);
            $this->assertSame('abaixo_meta', $escolas[1]['status']);
            DB::table('notas')->delete();
            $escolas = collect($this->getJson('/api/v1/dashboard/dir-nucleo')->assertOk()->json('data.escolas'))->keyBy('id');
            $this->assertSame(4, $escolas[1]['total_alunos']);
            $this->assertNull($escolas[1]['media']);
            $this->assertSame('sem_dados', $escolas[1]['status']);
        } finally {
            if ($depth !== null) DB::statement('SET SESSION optimizer_search_depth = '.$depth);
        }
    }

    public function test_numero_de_disciplinas_do_aluno_nao_cria_sql_por_disciplina(): void
    {
        $this->ator(5, 7);
        [$uma] = $this->medir('aluno');
        foreach (range(10, 12) as $id) { $this->prova($id, disciplina: 'Disciplina '.$id); $this->nota($id, 1, 8); }
        [$quatro] = $this->medir('aluno');
        foreach (range(13, 16) as $id) { $this->prova($id, disciplina: 'Disciplina '.$id); $this->nota($id, 1, 8); }
        [$oito, $r] = $this->medir('aluno');
        $r->assertJsonCount(8, 'data.notas');
        $this->assertLessThanOrEqual($uma + 2, $quatro);
        $this->assertLessThanOrEqual($uma + 2, $oito);
    }

    private function prepararSecretaria(): void
    {
        config(['vinculos.obrigatorio' => false]);
        DB::table('secretarias')->insert(['id' => 1, 'nome' => 'Secretaria fictícia', 'tipo' => 'estadual']);
        DB::table('redes')->where('id', 1)->update(['secretaria_id' => 1]);
        foreach (['secretaria', 'rede'] as $tipo) DB::table('usuario_escopos')->insert(['usuario_id' => 6, 'escopo_tipo' => $tipo, 'escopo_id' => 1]);
        $this->ator(6, null);
    }

    private function rede(int $id): void
    {
        DB::table('clientes')->insert(['id' => $id, 'nome' => 'Cliente fictício', 'tipo' => 'rede', 'modalidade' => 'interno', 'situacao' => 'ativo']);
        DB::table('redes')->insert(['id' => $id, 'secretaria_id' => 1, 'nome' => 'Rede fictícia '.$id, 'tipo' => 'estadual', 'uf' => 'BA']);
        DB::table('nucleos')->insert(['id' => $id, 'rede_id' => $id, 'nome' => 'Núcleo fictício']);
        DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => $id, 'nome' => 'Escola fictícia', 'inep' => str_pad((string) $id, 8, '0', STR_PAD_LEFT), 'tipo_rede' => 'estadual']);
        foreach (['rede' => 1000, 'nucleo' => 2000, 'escola' => 3000] as $tipo => $offset) DB::table('espacos')->insert([
            'id' => $id + $offset, 'cliente_id' => $id, 'tipo' => $tipo, 'nome' => 'Espaço fictício', $tipo.'_id' => $id, 'situacao' => 'ativo']);
        DB::table('usuario_escopos')->insert(['usuario_id' => 6, 'escopo_tipo' => 'rede', 'escopo_id' => $id]);
        $this->turma($id, escola: $id, espaco: 3000 + $id);
        $this->aluno($id, turma: $id, espaco: 3000 + $id);
        $this->prova($id, turma: $id, escola: $id, espaco: 3000 + $id);
        $this->nota($id, $id, 9, turma: $id, espaco: 3000 + $id);
    }

    public function test_secretaria_uma_quatro_oito_redes_e_medias_nao_ponderadas_por_alunos(): void
    {
        $this->prepararSecretaria();
        [$uma] = $this->medir('secretaria');
        foreach (range(10, 12) as $id) $this->rede($id);
        [$quatro] = $this->medir('secretaria');
        foreach (range(13, 16) as $id) $this->rede($id);
        foreach (range(50, 69) as $id) $this->aluno($id, turma: 10, espaco: 3010);
        [$oito, $r] = $this->medir('secretaria');
        $r->assertJsonCount(8, 'data.redes');
        $this->assertLessThanOrEqual($uma + 2, $quatro);
        $this->assertLessThanOrEqual($uma + 2, $oito);
        $redes = collect($r->json('data.redes'))->keyBy('id');
        $this->assertSame(6, $redes[1]['media']);
        $this->assertSame(9, $redes[10]['media']);
        $this->assertSame(21, $redes[10]['total_alunos']);
        $this->assertSame(1, $redes[10]['total_escolas']);
        $this->assertSame(6, $redes[1]['media']);
    }
}
