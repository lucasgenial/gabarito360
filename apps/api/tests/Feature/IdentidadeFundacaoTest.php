<?php

namespace Tests\Feature;

use App\Models\{Aluno, Usuario};
use App\Services\{ConsultaIdentidadeInterna, InventarioIdentidades};
use App\Support\{DadoProtegido, DocumentoIdentidade};
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class IdentidadeFundacaoTest extends TestCase
{
    use RefreshDatabase;

    private const CPF = '00000000191'; // Fixture matemática; não identifica nem verifica um titular.

    private function pessoa(): int
    {
        return DB::table('pessoas')->insertGetId(['uuid' => (string) Str::uuid(), 'situacao' => 'provisoria']);
    }

    private function documento(int $pessoa): int
    {
        return DB::table('pessoa_documentos')->insertGetId(['pessoa_id' => $pessoa, 'tipo' => 'CPF', 'pais' => 'BR',
            'valor_cifrado' => DocumentoIdentidade::cifrarCpf(self::CPF), 'situacao' => 'declarado', 'registrado_em' => now()]);
    }

    private function indice(int $documento, array $extra = []): void
    {
        DB::table('pessoa_documento_indices')->insert(array_replace(['documento_id' => $documento, 'tipo' => 'CPF',
            'pais' => 'BR', 'versao' => 1, 'indice' => DocumentoIdentidade::indiceCpf(self::CPF)], $extra));
    }

    private function recusa(callable $acao): void
    {
        try { $acao(); $this->fail('A integridade deveria recusar a operação.'); }
        catch (QueryException) { $this->addToAssertionCount(1); }
    }

    public function test_cpf_normaliza_mascara_sem_perder_zeros_e_rejeita_entradas_invalidas(): void
    {
        $this->assertSame(self::CPF, DocumentoIdentidade::normalizarCpf(' 000.000.001-91 '));
        foreach (['', '00000000000', '11111111111', '00000000190', '0000000019', '000x00000191', '000 000 001 91', '+00000000191'] as $cpf) {
            try { DocumentoIdentidade::normalizarCpf($cpf); $this->fail('Documento inválido aceito.'); }
            catch (\InvalidArgumentException $e) { $this->assertSame('Documento inválido.', $e->getMessage()); }
        }
    }

    public function test_cifra_autenticada_aleatoria_e_hmac_com_dominio_proprio(): void
    {
        $a = DocumentoIdentidade::cifrarCpf(self::CPF);
        $b = DocumentoIdentidade::cifrarCpf(self::CPF);
        $this->assertNotSame($a, $b);
        $this->assertStringNotContainsString(self::CPF, $a);
        $this->assertSame(self::CPF, DocumentoIdentidade::decifrarCpf($a));
        $this->assertSame(DocumentoIdentidade::indiceCpf(self::CPF), DocumentoIdentidade::indiceCpf('000.000.001-91'));
        $this->assertNotSame(DadoProtegido::hash(self::CPF, 'cpf'), DocumentoIdentidade::indiceCpf(self::CPF));
        $this->expectException(\Illuminate\Contracts\Encryption\DecryptException::class);
        DocumentoIdentidade::decifrarCpf('id1:v1:'.substr($a, 8).'x');
    }

    public function test_chave_ausente_falha_sem_revelar_documento(): void
    {
        config(['dados.hash_key' => null]);
        try { DocumentoIdentidade::indiceCpf(self::CPF); $this->fail('Chave ausente foi aceita.'); }
        catch (\RuntimeException $e) { $this->assertStringNotContainsString(self::CPF, $e->getMessage()); }
    }

    public function test_pessoa_sem_documento_nao_recebe_cpf_ficticio_ou_conta(): void
    {
        $this->pessoa();
        $this->assertDatabaseCount('pessoas', 1);
        $this->assertDatabaseCount('pessoa_documentos', 0);
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertNull(app(ConsultaIdentidadeInterna::class)->porCpf(self::CPF));
    }

    public function test_consulta_interna_exata_retorna_so_referencia_declarada_sem_confirmar_identidade(): void
    {
        $p = $this->pessoa(); $this->indice($this->documento($p));
        $r = app(ConsultaIdentidadeInterna::class)->porCpf('000.000.001-91');
        $this->assertSame(['pessoa_referencia' => DB::table('pessoas')->where('id', $p)->value('uuid'), 'documento_situacao' => 'declarado'], $r);
        $this->assertStringNotContainsString(self::CPF, json_encode($r));
        $this->assertNull(app(ConsultaIdentidadeInterna::class)->porCpf('00000000272'));
    }

    public function test_indice_unico_impede_reservar_o_mesmo_documento_em_duas_pessoas(): void
    {
        $this->indice($this->documento($this->pessoa()));
        $outro = $this->documento($this->pessoa());
        $this->recusa(fn () => $this->indice($outro));
        $this->assertDatabaseCount('pessoa_documento_indices', 1);
    }

    public function test_indice_nao_aceita_nova_versao_sem_rotacao_ou_fingerprint_invalido(): void
    {
        $d = $this->documento($this->pessoa());
        foreach ([['versao' => 2], ['indice' => 'curto'], ['indice' => str_repeat('G', 64)]] as $extra) {
            $this->recusa(fn () => $this->indice($d, $extra));
        }
        $this->assertDatabaseCount('pessoa_documento_indices', 0);
    }

    public function test_documentos_indices_e_eventos_nao_podem_ser_reescritos_ou_excluidos(): void
    {
        $p = $this->pessoa(); $d = $this->documento($p); $this->indice($d);
        DB::table('pessoa_identidade_eventos')->insert(['referencia' => (string) Str::uuid(), 'pessoa_id' => $p,
            'documento_id' => $d, 'acao' => 'documento_declarado', 'ocorrido_em' => now()]);
        foreach (['pessoa_documentos', 'pessoa_documento_indices', 'pessoa_identidade_eventos'] as $t) {
            $this->recusa(fn () => DB::table($t)->update(['id' => 999]));
            $this->recusa(fn () => DB::table($t)->delete());
            $this->assertDatabaseCount($t, 1);
        }
        $this->recusa(fn () => DB::table('pessoas')->where('id', $p)->delete());
    }

    public function test_evento_nao_pode_referenciar_documento_de_outra_pessoa(): void
    {
        $d = $this->documento($this->pessoa());
        $this->recusa(fn () => DB::table('pessoa_identidade_eventos')->insert(['referencia' => (string) Str::uuid(),
            'pessoa_id' => $this->pessoa(), 'documento_id' => $d, 'acao' => 'documento_declarado', 'ocorrido_em' => now()]));
    }

    private function legado(): void
    {
        Usuario::create(['perfil' => 'professor', 'nome' => 'Pessoa fictícia', 'email' => 'identidade@example.test',
            'cpf' => self::CPF, 'password' => 'senha-ficticia']);
        $r = DB::table('redes')->insertGetId(['nome' => 'Rede fictícia', 'tipo' => 'estadual', 'uf' => 'BA']);
        $n = DB::table('nucleos')->insertGetId(['rede_id' => $r, 'nome' => 'Núcleo fictício']);
        foreach ([1, 2] as $id) {
            $e = DB::table('escolas')->insertGetId(['nucleo_id' => $n, 'nome' => 'Escola fictícia', 'inep' => '2900000'.$id, 'tipo_rede' => 'estadual']);
            $t = DB::table('turmas')->insertGetId(['escola_id' => $e, 'nome' => 'Turma fictícia', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            // O legado indexa o texto recebido; máscara distinta pode esconder a mesma identidade.
            Aluno::create(['turma_id' => $t, 'nome' => 'Aluno fictício', 'matricula' => 'ID1-'.$id, 'cpf' => $id === 1 ? self::CPF : '000.000.001-91']);
        }
    }

    public function test_inventario_agrupa_documentos_sem_expor_pessoas_nem_fundir_cadastros(): void
    {
        $this->legado();
        $antes = DB::table('alunos')->orderBy('id')->get()->toJson();
        $r = app(InventarioIdentidades::class)->executar();
        $this->assertSame(1, $r['fontes']['usuarios']['digitos_validos']);
        $this->assertSame(2, $r['fontes']['alunos']['digitos_validos']);
        $this->assertSame(1, $r['grupos_repetidos']);
        $this->assertSame(1, $r['grupos_entre_conta_e_aluno']);
        $this->assertSame(1, $r['grupos_em_multiplos_alunos']);
        foreach ([self::CPF, 'Aluno fictício', 'identidade@example.test', DocumentoIdentidade::indiceCpf(self::CPF)] as $segredo) $this->assertStringNotContainsString($segredo, json_encode($r));
        $this->assertSame($antes, DB::table('alunos')->orderBy('id')->get()->toJson());
        $this->assertDatabaseCount('pessoas', 0);
    }

    public function test_inventario_classifica_ausente_invalido_e_ilegivel_sem_saida_sensivel(): void
    {
        $this->legado();
        $alunos = Aluno::orderBy('id')->get();
        $alunos[0]->update(['cpf' => null]);
        $alunos[1]->update(['cpf' => '00000000000']);
        DB::table('usuarios')->update(['cpf' => 'conteudo-incompativel']);
        $r = app(InventarioIdentidades::class)->executar();
        $this->assertSame(1, $r['fontes']['alunos']['sem_documento']);
        $this->assertSame(1, $r['fontes']['alunos']['invalidos']);
        $this->assertSame(1, $r['fontes']['usuarios']['ilegiveis']);
        $this->assertSame(0, $r['grupos_repetidos']);
        $this->assertStringNotContainsString('conteudo-incompativel', json_encode($r));
    }

    public function test_comando_recusa_volume_ou_chave_ausente_sem_escrita_e_sem_inventario_parcial(): void
    {
        $this->legado();
        $this->artisan('g360:identidades-inventariar', ['--limite' => 1])->assertFailed()
            ->expectsOutput('Inventário indisponível. Confira configuração, esquema e limite; nenhuma escrita foi executada.');
        config(['dados.hash_key' => null]);
        $this->artisan('g360:identidades-inventariar')->assertFailed();
        $this->assertDatabaseCount('pessoas', 0);
        $this->assertDatabaseCount('alunos', 2);
    }
}
