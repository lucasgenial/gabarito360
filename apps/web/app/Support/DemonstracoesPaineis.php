<?php

declare(strict_types=1);

namespace App\Support;

/** Exemplos visuais estáticos. Nunca são fonte de indicadores, relatórios ou operações. */
final class DemonstracoesPaineis
{
    public const AVISO = 'Dados demonstrativos; não representam os filtros aplicados';

    /** O chamador deve primeiro autorizar o componente pelo catálogo do contexto atual. */
    public static function para(string $id): ?array
    {
        $conteudo = match ($id) {
            'comparacao-turmas' => self::barras(
                ['Turma fictícia Aurora','Turma fictícia Horizonte','Turma fictícia Ipê'], [65,72,80],
                'Acertos médios', 'Exemplo do mesmo instrumento de 20 itens, com 10 resultados por turma.',
                ['itens'=>20,'resultados_por_turma'=>10,'acertos'=>[130,144,160],'respostas_possiveis'=>[200,200,200]]),
            'cobertura-disciplinas' => self::barras(
                ['Matemática ilustrativa','Ciências ilustrativas','Linguagens ilustrativas'], [60,80,100],
                'Aplicações com evidência', 'Exemplo: 6, 8 e 10 de 10 aplicações previstas possuem evidências. Não representa presença de estudantes.',
                ['com_evidencia'=>[6,8,10],'aplicacoes_previstas'=>[10,10,10],'unidade'=>'aplicações com evidência']),
            'periodos-escolares' => self::barras(
                ['1º trimestre ilustrativo','2º trimestre ilustrativo','3º trimestre ilustrativo'], [62,69,76],
                'Acertos no exemplo', 'Instrumentos comparáveis fictícios: 100 participações e 20 itens por trimestre. A mudança ilustrada não demonstra ganho de aprendizagem.',
                ['participacoes'=>[100,100,100],'itens'=>20,'acertos'=>[1240,1380,1520],'respostas_possiveis'=>[2000,2000,2000]]),
            'habilidades' => self::barras(
                ['H1 · Interpretar texto','H2 · Resolver problema','H3 · Ler gráfico','H4 · Justificar resposta'], [70,55,80,45],
                'Acertos por descritor ilustrativo', 'Descritores de exemplo, sem vínculo com uma matriz oficial: 10 resultados de 4 itens por habilidade.',
                ['resultados_por_habilidade'=>10,'itens_por_habilidade'=>4,'acertos'=>[28,22,32,18],'respostas_possiveis'=>[40,40,40,40]]),
            'agenda-pedagogica', 'proximos-compromissos' => self::agenda(false),
            'agenda-administrativa' => self::agenda(true),
            'devolutivas' => self::progresso(18,24,
                'Exemplo de 24 devolutivas: 18 concluídas e 6 pendentes; prazos ilustrativos de novembro de 2026.',
                [['titulo'=>'Devolutivas concluídas','data'=>'2026-11-09','hora'=>'16:00','situacao'=>'Concluída','responsavel'=>'Docente fictício Lira','contexto'=>'18 de 24 devolutivas ilustrativas'],
                    ['titulo'=>'Devolutivas pendentes','data'=>'2026-11-13','hora'=>'17:00','situacao'=>'Pendente','responsavel'=>'Docente fictício Lira','contexto'=>'6 de 24 devolutivas ilustrativas']],
                ['concluidas'=>18,'pendentes'=>6]),
            'participacao' => self::progresso(16,18,
                'Exemplo de 20 estudantes elegíveis: 2 dispensados; entre os 18 previstos, 16 presenças e 2 ausências confirmadas. Não deriva de cartões recebidos.',
                [['titulo'=>'Aplicação ilustrativa','data'=>'2026-11-10','hora'=>'08:00','situacao'=>'Registro ilustrativo completo','responsavel'=>'Docente fictício Lira','contexto'=>'Turma fictícia Aurora · 16 presenças de 18 previstas']],
                ['presencas'=>16,'ausencias_confirmadas'=>2], ['elegiveis'=>20,'dispensados'=>2,'previstos'=>18]),
            'intervencoes', 'prioridades' => self::tarefas(),
            'reavaliacoes' => self::barras(
                ['Grupo fictício Aurora','Grupo fictício Horizonte'], [55,70],
                'Resultado na reavaliação ilustrativa', 'Exemplo com 10 estudantes e 20 itens em cada instrumento comparável. Não substitui a avaliação original nem comprova efeito de uma intervenção.',
                ['resultados'=>[10,10],'acertos'=>[110,140],'respostas_possiveis'=>[200,200]]),
            'metas' => self::progresso(3,5,
                'Exemplo de cinco ações de acompanhamento planejadas para o período: três concluídas e duas pendentes. Não é meta de nota ou proficiência presumida.',
                [['titulo'=>'Revisar cinco sequências de acompanhamento','data'=>'2026-11-27','hora'=>'17:00','situacao'=>'Em andamento','responsavel'=>'Coordenação fictícia Acácia','contexto'=>'3 de 5 ações ilustrativas concluídas']],
                ['concluidas'=>3,'pendentes'=>2]),
            'conselho' => self::registros('Exemplo de encaminhamentos de um conselho fictício; nenhuma decisão foi registrada no sistema.', [
                ['titulo'=>'Revisar as evidências da turma fictícia Aurora','data'=>'2026-11-18','hora'=>'14:00','situacao'=>'Preparação','responsavel'=>'Coordenação fictícia Acácia','contexto'=>'Reunir registros ilustrativos antes do conselho'],
                ['titulo'=>'Planejar retorno às famílias fictícias','data'=>'2026-11-20','hora'=>'16:00','situacao'=>'Encaminhamento ilustrativo','responsavel'=>'Docente fictício Lira','contexto'=>'Prazo de retorno e responsável definidos no exemplo'],
            ]),
            'responsabilidade-docente-aplicacao' => self::registros('Exemplo distingue autor, responsável na aplicação e docente atual; não atribui responsabilidade usando apenas autoria ou lotação atual.', [
                ['titulo'=>'Aplicação fictícia de Matemática','data'=>'2026-11-10','hora'=>'08:00','situacao'=>'Histórico ilustrativo','responsavel'=>'Docente fictício Lira','contexto'=>'Turma fictícia Aurora · autor: Coordenação fictícia Acácia · responsável na aplicação: Docente fictício Lira · docente atual: Docente fictício Cedro'],
                ['titulo'=>'Aplicação fictícia de Ciências','data'=>'2026-11-11','hora'=>'10:00','situacao'=>'Histórico ilustrativo','responsavel'=>'Docente fictício Cedro','contexto'=>'Turma fictícia Horizonte · autor e responsável na aplicação: Docente fictício Cedro'],
            ]),
            'documentos-administrativos' => self::registros('Exemplo de solicitações cadastrais; não contém avaliações, notas ou registros operacionais reais.', [
                ['titulo'=>'Conferência cadastral fictícia','data'=>'2026-11-12','hora'=>'09:00','situacao'=>'Em conferência','responsavel'=>'Secretaria fictícia Oliva','contexto'=>'Cadastro ilustrativo A · documento de matrícula'],
                ['titulo'=>'Declaração cadastral fictícia','data'=>'2026-11-13','hora'=>'11:00','situacao'=>'Aguardando emissão','responsavel'=>'Secretaria fictícia Oliva','contexto'=>'Cadastro ilustrativo B · solicitação administrativa'],
                ['titulo'=>'Atualização cadastral fictícia','data'=>'2026-11-09','hora'=>'15:00','situacao'=>'Concluída','responsavel'=>'Secretaria fictícia Oliva','contexto'=>'Cadastro ilustrativo C · conferência finalizada no exemplo'],
            ]),
            default => null,
        };
        return $conteudo === null ? null : ['id'=>$id,'demonstrativo'=>true,'aviso'=>self::AVISO] + $conteudo;
    }

    private static function barras(array $categorias, array $valores, string $serie, string $descricao, array $base): array
    {
        return ['tipo'=>'grafico','descricao'=>$descricao,'base'=>$base,
            'grafico'=>['categorias'=>$categorias,'series'=>[['nome'=>$serie,'valores'=>$valores]],
                'formato'=>'pct','maximo'=>100,'rotulos'=>true]];
    }

    private static function agenda(bool $administrativa): array
    {
        $itens = $administrativa ? [
            ['titulo'=>'Conferência cadastral fictícia','data'=>'2026-11-09','hora'=>'09:00','situacao'=>'Previsto','responsavel'=>'Secretaria fictícia Oliva','contexto'=>'Turma fictícia Aurora · conferência de documentos'],
            ['titulo'=>'Entrega de declaração fictícia','data'=>'2026-11-10','hora'=>'11:00','situacao'=>'Previsto','responsavel'=>'Secretaria fictícia Oliva','contexto'=>'Turma fictícia Horizonte · solicitação cadastral'],
            ['titulo'=>'Reunião administrativa fictícia','data'=>'2026-11-12','hora'=>'14:00','situacao'=>'Previsto','responsavel'=>'Secretaria fictícia Oliva','contexto'=>'Organização dos arquivos cadastrais'],
        ] : [
            ['titulo'=>'Aplicação fictícia de Matemática','data'=>'2026-11-09','hora'=>'08:00','situacao'=>'Previsto','responsavel'=>'Docente fictício Lira','contexto'=>'Turma fictícia Aurora · sala ilustrativa A'],
            ['titulo'=>'Devolutiva fictícia de Ciências','data'=>'2026-11-10','hora'=>'10:00','situacao'=>'Previsto','responsavel'=>'Docente fictício Cedro','contexto'=>'Turma fictícia Horizonte · encontro ilustrativo'],
            ['titulo'=>'Reunião fictícia de acompanhamento','data'=>'2026-11-12','hora'=>'14:00','situacao'=>'Previsto','responsavel'=>'Coordenação fictícia Acácia','contexto'=>'Turma fictícia Aurora · planejamento ilustrativo'],
        ];
        return ['tipo'=>'agenda','descricao'=>'Semana ilustrativa de 9 a 15 de novembro de 2026. Os eventos não existem no calendário operacional.',
            'inicio'=>'2026-11-09','fim'=>'2026-11-15','itens'=>$itens];
    }

    private static function progresso(int $concluidas, int $total, string $descricao, array $itens, array $situacoes, array $base = []): array
    {
        return ['tipo'=>'progresso','descricao'=>$descricao,'concluidas'=>$concluidas,'total'=>$total,
            'percentual'=>round(100 * $concluidas / $total,1),'situacoes'=>$situacoes,'itens'=>$itens,'base'=>$base];
    }

    private static function tarefas(): array
    {
        return ['tipo'=>'tarefas','descricao'=>'Três ações fictícias de acompanhamento, com responsáveis e prazos ilustrativos; nenhuma ação real foi criada.',
            'total'=>3,'situacoes'=>['pendentes'=>1,'em_andamento'=>1,'concluidas'=>1],'itens'=>[
                ['titulo'=>'Retomar leitura de gráficos no grupo fictício Aurora','data'=>'2026-11-13','hora'=>'10:00','situacao'=>'Pendente','responsavel'=>'Docente fictício Lira','contexto'=>'Planejar estratégia e registrar nova evidência'],
                ['titulo'=>'Acompanhar resolução de problemas no grupo fictício Horizonte','data'=>'2026-11-16','hora'=>'14:00','situacao'=>'Em andamento','responsavel'=>'Docente fictício Cedro','contexto'=>'Estratégia ilustrativa em execução'],
                ['titulo'=>'Organizar retorno do grupo fictício Ipê','data'=>'2026-11-09','hora'=>'16:00','situacao'=>'Concluída','responsavel'=>'Coordenação fictícia Acácia','contexto'=>'Ação concluída no exemplo; resultado posterior ainda não inferido'],
            ]];
    }

    private static function registros(string $descricao, array $itens): array
    {
        return ['tipo'=>'registros','descricao'=>$descricao,'itens'=>$itens];
    }
}
