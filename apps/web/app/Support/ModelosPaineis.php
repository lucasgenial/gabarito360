<?php

namespace App\Support;

/** Catálogo visual P1/P2 (docs177/180); dados ilustrativos ficam em factory própria. */
final class ModelosPaineis
{
    public const ESTADOS = ['demonstrativo', 'carregando', 'vazio', 'zero', 'parcial', 'erro', 'funcional'];

    /**
     * Recebe somente o perfil e as permissões do contexto autenticado atual.
     * Ao ativar uma fonte, preservar ID e posição do bloco; ausência de registros será vazio, não modelo.
     */
    public static function para(string $pagina, string $perfil, array $permissoes): array
    {
        if (!in_array($pagina, ['turmas', 'turma'], true) || !in_array('turma.ver', $permissoes, true)) return [];
        $administrativo = in_array($perfil, ['secretario_escolar', 'auxiliar_secretaria'], true);
        $pedagogico = !$administrativo && in_array('relatorio.turma', $permissoes, true);
        $lista = [];
        foreach (self::catalogo() as $id => $item) {
            if (!in_array($pagina, $item['paginas'], true)) continue;
            if ($item['publico'] === 'pedagogico' && !$pedagogico) continue;
            if ($item['publico'] === 'administrativo' && !$administrativo) continue;
            if (!in_array($item['permissao'], $permissoes, true)) continue;
            $lista[] = ['id'=>$id, 'estado'=>'demonstrativo', 'selo'=>'Em breve', 'pagina'=>$pagina,
                'posicao'=>array_search($id,array_keys(self::catalogo()),true)+1, 'tamanho'=>'meia-largura', 'celular'=>'largura-total',
                'filtros'=>'Dados demonstrativos; não representam os filtros aplicados.',
                'ativacao'=>'Fonte implementada, contrato autorizado e validação independente; zero registros passa a vazio funcional.',
                'visual'=>'em implementação', 'funcional'=>'dependência pendente', 'grupo'=>self::grupo($id)] + $item;
        }
        return $lista;
    }

    private static function catalogo(): array
    {
        return [
            'prioridades'=>self::item('Prioridades de acompanhamento', 'Reúna prazos e encaminhamentos registrados para organizar os próximos retornos.', 'equipe', ['turmas'], 'TU1 / C4 / P6.2', 'Prazos, responsáveis e encaminhamentos reais; não substitui o grupo Atenção.'),
            'comparacao-turmas'=>self::item('Resultados entre turmas', 'Compare resultados do mesmo instrumento, com bases e recortes identificados.', 'grafico', ['turmas'], 'P3.3', 'Agregado autorizado por avaliação; não usar média de médias.'),
            'cobertura-disciplinas'=>self::item('Cobertura por disciplina', 'Acompanhe quais turmas e disciplinas possuem evidências de avaliação.', 'grafico', ['turmas'], 'P4.4 / MT1', 'Conjunto esperado e evidências válidas por turma e disciplina.'),
            'participacao'=>self::item('Participação nas aplicações', 'Acompanhe a participação registrada nas avaliações, com uma base histórica confiável.', 'turma', ['turma'], 'MT1 / P4.4', 'Elegibilidade histórica, participação e dispensa; cartão não comprova presença.'),
            'responsabilidade-docente-aplicacao'=>self::item('Responsabilidade docente na aplicação', 'Consulte quem foi responsável no momento da aplicação, preservando o histórico.', 'equipe', ['turmas','turma'], 'DC1 / P1.2.3', 'Responsabilidade temporal registrada na aplicação; não confundir com autor ou docente atual.'),
            'periodos-escolares'=>self::item('Períodos escolares', 'Organize o acompanhamento pelos períodos do calendário da turma.', 'prova', ['turma'], 'P4', 'Calendário e períodos versionados; datas não presumem bimestre.'),
            'agenda-pedagogica'=>self::item('Agenda da turma', 'Aplicações, devolutivas e compromissos deste período aparecerão aqui.', 'prova', ['turma'], 'C2/C3 / P5', 'Integração futura: eventos autorizados, datas e situação.'),
            'proximos-compromissos'=>self::item('Próximos compromissos', 'Reúna os próximos compromissos das suas turmas em um só lugar.', 'prova', ['turmas'], 'C2/C3 / P5', 'Eventos do alcance atual; sem lembretes inferidos.'),
            'devolutivas'=>self::item('Devolutivas', 'Acompanhe os retornos previstos para os estudantes e seus prazos.', 'prova', ['turmas','turma'], 'C4 / N1–N3 / P5', 'Obrigação registrada, prazo e situação de devolutiva.'),
            'habilidades'=>self::item('Habilidades em acompanhamento', 'Relacione as evidências das avaliações às habilidades trabalhadas.', 'grafico', ['turma'], 'P6.1', 'Classificação revisada e evidência mínima; área não equivale a habilidade.'),
            'intervencoes'=>self::item('Ações de acompanhamento', 'Organize estratégias, responsáveis e retornos a partir das evidências.', 'equipe', ['turmas','turma'], 'TU1 / P6.2', 'Necessidade, ação, responsável, prazo e retorno registrados.'),
            'reavaliacoes'=>self::item('Reavaliações', 'Acompanhe novas evidências sem perder os resultados anteriores.', 'grafico', ['turma'], 'P6.2 / P6.3', 'Instrumentos e regra de composição; não presume ganho ou causalidade.'),
            'metas'=>self::item('Metas do período', 'Acompanhe metas registradas, seus critérios e os retornos previstos.', 'grafico', ['turma'], 'P6.4', 'Situação inicial, critério, período e responsável; sem meta presumida.'),
            'conselho'=>self::item('Conselho de classe', 'Reúna encaminhamentos e retornos autorizados para a turma.', 'equipe', ['turma'], 'P6.4', 'Decisões e observações com autorização própria.', 'pedagogico', 'turma.vincular_professor'),
            'agenda-administrativa'=>self::item('Agenda administrativa', 'Consulte os compromissos administrativos da turma, sem informações de avaliações.', 'escola', ['turmas','turma'], 'C2/C3 / P5', 'Projeção administrativa de eventos, sem títulos ou contadores pedagógicos.', 'administrativo', 'turma.ver'),
            'documentos-administrativos'=>self::item('Documentos cadastrais', 'Acompanhe solicitações e documentos cadastrais autorizados.', 'aluno', ['turmas','turma'], 'Módulo administrativo / P2', 'Solicitações cadastrais reais e política específica; não inclui relatórios de notas.', 'administrativo', 'aluno.ver'),
        ];
    }

    private static function grupo(string $id): string
    {
        return match($id) {
            'comparacao-turmas','cobertura-disciplinas'=>'analises',
            'habilidades','intervencoes','reavaliacoes'=>'ensino',
            'metas','conselho'=>'decisoes',
            'agenda-administrativa','documentos-administrativos'=>'administrativo',
            default=>'planejamento',
        };
    }

    private static function item(string $titulo, string $finalidade, string $icone, array $paginas, string $dependencia, string $fonte, string $publico='pedagogico', string $permissao='relatorio.turma'): array
    {
        return compact('titulo','finalidade','icone','paginas','dependencia','fonte','publico','permissao');
    }
}
