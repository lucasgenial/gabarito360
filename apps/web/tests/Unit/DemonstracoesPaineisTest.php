<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\DemonstracoesPaineis;
use PHPUnit\Framework\TestCase;

class DemonstracoesPaineisTest extends TestCase
{
    private const IDS = ['comparacao-turmas','cobertura-disciplinas','periodos-escolares','habilidades',
        'agenda-pedagogica','proximos-compromissos','agenda-administrativa','devolutivas','participacao',
        'intervencoes','prioridades','reavaliacoes','metas','conselho','responsabilidade-docente-aplicacao','documentos-administrativos'];

    public function test_cada_modelo_tem_marcacao_inseparavel_e_composicao_sem_dependencia_de_aplicacao(): void
    {
        // TestCase puro: sem Laravel, sessão, banco, serviços ou contexto real inicializados.
        foreach (self::IDS as $id) {
            $demo = DemonstracoesPaineis::para($id);
            $this->assertNotNull($demo);
            $this->assertSame($id,$demo['id']);
            $this->assertTrue($demo['demonstrativo']);
            $this->assertSame('Dados demonstrativos; não representam os filtros aplicados',$demo['aviso']);
            $this->assertNotEmpty($demo['descricao']);
            $this->assertContains($demo['tipo'],['grafico','agenda','progresso','tarefas','registros']);
            $this->assertNotEmpty($demo[$demo['tipo']==='grafico'?'grafico':'itens']);
            $this->assertSemOperacao($demo);
        }
        foreach (['desconhecido','', '../comparacao-turmas','<script>','agenda','resultado-real'] as $id) $this->assertNull(DemonstracoesPaineis::para($id));
    }

    public function test_graficos_percentuais_conferem_com_numeradores_denominadores_e_mesmo_instrumento(): void
    {
        foreach (['comparacao-turmas','periodos-escolares','habilidades','reavaliacoes'] as $id) {
            $demo = DemonstracoesPaineis::para($id);
            $grafico = $demo['grafico'];
            $this->assertSame('pct',$grafico['formato']);
            $this->assertSame(100,$grafico['maximo']);
            $this->assertTrue($grafico['rotulos']);
            $this->assertCount(count($grafico['categorias']),$grafico['series'][0]['valores']);
            foreach ($demo['base']['acertos'] as $i=>$acertos) {
                $denominador = $demo['base']['respostas_possiveis'][$i];
                $this->assertGreaterThan(0,$denominador);
                $this->assertLessThanOrEqual($denominador,$acertos);
                $this->assertEquals(round(100 * $acertos / $denominador,1),$grafico['series'][0]['valores'][$i]);
            }
        }
        $comparacao = DemonstracoesPaineis::para('comparacao-turmas');
        $this->assertSame([65,72,80],$comparacao['grafico']['series'][0]['valores']);
        $this->assertSame(20 * 10,$comparacao['base']['respostas_possiveis'][0]);
        foreach ($comparacao['grafico']['categorias'] as $nome) $this->assertStringContainsString('fictícia',$nome);
        $periodos = DemonstracoesPaineis::para('periodos-escolares');
        $this->assertSame([100,100,100],$periodos['base']['participacoes']);
        $this->assertSame([2000,2000,2000],$periodos['base']['respostas_possiveis']);
    }

    public function test_cobertura_mede_aplicacoes_com_evidencia_e_nao_presenca(): void
    {
        $demo = DemonstracoesPaineis::para('cobertura-disciplinas');
        $this->assertSame('aplicações com evidência',$demo['base']['unidade']);
        foreach ($demo['base']['com_evidencia'] as $i=>$total) $this->assertEquals(100 * $total / $demo['base']['aplicacoes_previstas'][$i],$demo['grafico']['series'][0]['valores'][$i]);
        $this->assertStringContainsString('Não representa presença',$demo['descricao']);
    }

    public function test_progressos_e_participacao_possuem_bases_explicitas_e_totais_coerentes(): void
    {
        foreach (['devolutivas','participacao','metas'] as $id) {
            $demo = DemonstracoesPaineis::para($id);
            $this->assertSame($demo['total'],array_sum($demo['situacoes']));
            $this->assertGreaterThan(0,$demo['total']);
            $this->assertLessThanOrEqual($demo['total'],$demo['concluidas']);
            $this->assertEquals(round(100 * $demo['concluidas'] / $demo['total'],1),$demo['percentual']);
        }
        $devolutivas = DemonstracoesPaineis::para('devolutivas');
        $this->assertSame(18,$devolutivas['concluidas']);
        $this->assertSame(24,$devolutivas['total']);
        $this->assertSame(75.0,$devolutivas['percentual']);
        $participacao = DemonstracoesPaineis::para('participacao');
        $this->assertSame($participacao['base']['elegiveis'] - $participacao['base']['dispensados'],$participacao['total']);
        $this->assertSame(88.9,$participacao['percentual']);
        $tarefas = DemonstracoesPaineis::para('intervencoes');
        $this->assertSame($tarefas['total'],array_sum($tarefas['situacoes']));
        $this->assertCount($tarefas['total'],$tarefas['itens']);
    }

    public function test_agendas_e_tarefas_usam_datas_horarios_validos_e_responsaveis_ficticios(): void
    {
        foreach (self::IDS as $id) {
            $demo = DemonstracoesPaineis::para($id);
            foreach ($demo['itens']??[] as $item) {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d',$item['data']);
                $this->assertNotFalse($date);
                $this->assertSame($item['data'],$date->format('Y-m-d'));
                $this->assertSame('2026',$date->format('Y'));
                $this->assertMatchesRegularExpression('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',$item['hora']);
                $this->assertMatchesRegularExpression('/fictíci[oa]/u',$item['responsavel']);
                $this->assertNotEmpty($item['situacao']);
                $this->assertNotEmpty($item['contexto']);
                if ($demo['tipo']==='agenda') {
                    $this->assertGreaterThanOrEqual($demo['inicio'],$item['data']);
                    $this->assertLessThanOrEqual($demo['fim'],$item['data']);
                }
            }
        }
        $administrativa = json_encode(DemonstracoesPaineis::para('agenda-administrativa'),JSON_UNESCAPED_UNICODE);
        foreach (['Matemática','avaliação','nota','Ciências','recuperação'] as $termo) $this->assertStringNotContainsString($termo,$administrativa);
    }

    public function test_retorno_independente_nao_persiste_alteracoes_ou_recebe_dados_reais(): void
    {
        $demo = DemonstracoesPaineis::para('comparacao-turmas');
        $original = $demo;
        $demo['grafico']['series'][0]['valores'][0] = 999;
        $demo['demonstrativo'] = false;
        $this->assertSame($original,DemonstracoesPaineis::para('comparacao-turmas'));
    }

    private function assertSemOperacao(array $dados): void
    {
        foreach ($dados as $chave=>$valor) {
            $this->assertNotContains($chave,['url','href','endpoint','method','action','token','prova_id','aluno_id','usuario_id','espaco_id','salvar','enviar','agenda_id']);
            if (is_array($valor)) $this->assertSemOperacao($valor);
            elseif (is_string($valor)) $this->assertDoesNotMatchRegularExpression('~https?://|/api/|/app/|javascript:~i',$valor);
        }
    }
}
