# P3.2 — Indicadores e gráficos atuais

Planejamento revisado antes da implementação em08/11/2026. Base: main e457f6d, árvore homologada4095a304. P3.1 e as correções dosPRs216–219 estão concluídos; as pendências históricas do doc179 não reabrem essas entregas. Evidências: operacao-20261102/paineis-lateral-entrega-final.json. Produção excluída desta autorização.

## Reconciliação e entrega

| Item | Fonte atual | Trabalho deste incremento |
|---|---|---|
| P3.2.1 | Seis KPIs reais | Conciliar bases e corrigir descrição de cadastros distintos |
| P3.2.2 | Distribuição, média/mediana e situações reais | Explicitar bases/exclusões; manter ECharts, H/V e geometria |
| P3.2.3 | Estados das leituras na API e documentos, sob permissão | Validar regressões; não ressuscitar o card removido pelo responsável |
| P3.2.4 | Atenção, Dentro do critério e Top5 no DTO | Preservar significado, empates e expansão; dois cards nominais na tela |
| P3.2.5 | Base percentual global e alternativas acessíveis | Completar base por instrumento, ponderação e momento da consulta |
| P3.2.6 | Filtro curricular seleciona avaliação completa | Identificar resultado global; não inventar resultado disciplinar por itens |

Pré-consulta UI/UX e Product Design aprovada em paineis-p32-design-previo.md, com captura atual da homologação. Títulos existentes acionam popover textual, sem nova coluna, card, subtítulo ou modal. Teclado, toque, Escape, clique fora e foco devem funcionar; o painel não muda de tamanho. Tooltips dos gráficos mostram a mesma base de cálculo. Nenhum nome de aluno é serializado para os gráficos.

## Contrato e limites

A API recebe somente campos aditivos de base total, exclusões e abrangência por avaliação. Reutilizar a agregação existente, sem consulta por item. Nenhuma rota, permissão, filtro, sessão, ID de widget ou preferência é alterada. Resultados percentuais excluem denominadores inválidos; a situação registrada continua contabilizada em todos os resultados concluídos autorizados. Não transformar ausência de informação em zero.

O momento da consulta é apresentado pela Web após a resposta, com rótulo Consultado em; não representa a última alteração dos dados. Não introduzir relógio volátil no DTO da API: o PDF remonta e compara seu hash para detectar alterações reais. Não alterar esse guard. Preservar documentos, histórico da turma da aplicação, autoria original, máscaras curriculares e revogação entre usos.

## Aceitação

Regressões de dados vazios/zero/100%, percentuais inválidos, instrumentos com bases diferentes, exclusões por instrumento, provas multidisciplinares, alunos sem resultado, empates e permissões retiradas. Comparar número de consultas em lote. Conferir PDFs sem conflito espúrio e revisão de cartões pendente excluída dos resultados finais.

Revisão independente de API/segurança e UI, Chrome desktop/mobile, CI completo, merge e publicação exclusiva na homologação com backup e comparação das tabelas/arquivos. Sem migration e sem escrita nos dados de gabaritos. Fechar com tabela inline atualizada; P3.3 é o próximo pacote e P3.4 regional permanece parcial.
