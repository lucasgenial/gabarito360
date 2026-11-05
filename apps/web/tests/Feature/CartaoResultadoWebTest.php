<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CartaoResultadoWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url' => 'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token' => 'ficticio', 'auth_perfil' => 'coordenador', 'auth_nome' => 'Pessoa fictícia', 'auth_vinculos' => false, 'auth_permissoes' => ['correcao.acompanhar', 'correcao.alterar']]);
    }

    private function resultado(?string $url): array
    {
        return ['aluno' => ['id' => 1, 'nome' => 'Aluno fictício', 'matricula' => 'TESTE-1', 'turma_nome' => 'Turma fictícia', 'turma_id' => 1],
            'prova' => ['id' => 4, 'titulo' => 'Prova fictícia', 'disciplina' => 'Teste', 'nota_maxima' => 10, 'num_alternativas' => 5],
            'nota' => ['nota_final' => 5, 'acertos' => 1, 'total_questoes' => 2], 'folha' => [['numero_questao' => 1, 'situacao' => 'correta', 'marcada' => 'A', 'gabarito' => 'A', 'ambigua' => false], ['numero_questao' => 2, 'situacao' => 'branco', 'marcada' => null, 'gabarito' => 'B', 'ambigua' => false]],
            'lingua' => null, 'cartao_id' => 65, 'cartao_foto_url' => $url, 'correcao' => ['fechada' => false], 'media_turma' => null];
    }

    public function test_resultado_oferece_painel_sob_demanda_e_modal_sem_nova_aba(): void
    {
        Http::fake(['*' => Http::response(['data' => $this->resultado('/api/v1/cartoes/65/arquivo/original?signature=ficticia')])]);
        $this->get('/resultados/1/4?from=correcao')->assertOk()->assertSee('Visualizar cartão-resposta')->assertSee('doc-painel')->assertSee('doc-modal')->assertSee('painel-cartao.js')->assertSee('Histórico de tentativas');
    }

    public function test_resultado_sem_autorizacao_de_foto_nao_oferece_leitor(): void
    {
        Http::fake(['*' => Http::response(['data' => $this->resultado(null)])]);
        $this->get('/resultados/1/4')->assertOk()->assertDontSee('doc-painel')->assertDontSee('Visualizar cartão-resposta');
    }

    public function test_foto_antiga_inadequada_mostra_aviso_sem_alterar_o_resultado(): void
    {
        $d = $this->resultado('/foto-autorizada.jpg');
        $d['qualidade_foto'] = ['aprovada' => false, 'mensagem' => 'Marcador incompleto.'];
        Http::fake(['*' => Http::response(['data' => $d])]);
        $this->get('/resultados/1/4')->assertOk()->assertSee('Marcador incompleto.')->assertSee('As respostas e a nota já registradas foram preservadas.')->assertSee('5,0');
    }

    public function test_retirada_encaminha_motivo_versao_e_contexto_para_api(): void
    {
        Http::fake(['*' => Http::response(['data' => ['excluido' => true]])]);
        $this->deleteJson('/cartoes/65', ['motivo' => 'Retirada documentada de teste', 'versao' => str_repeat('a', 64)])->assertOk();
        Http::assertSent(fn ($r) => $r->method() === 'DELETE' && $r['motivo'] === 'Retirada documentada de teste' && $r['versao'] === str_repeat('a', 64) && $r->hasHeader('Authorization', 'Bearer ficticio'));
    }
}
