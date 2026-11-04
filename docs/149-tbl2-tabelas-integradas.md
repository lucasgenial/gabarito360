# TBL2 — tabelas e cards dentro da superfície principal

04/11/2026. Planejamento revisado antes da implementação após aprovação da tabela compacta de vínculos e solicitação de estender o padrão às tabelas da aplicação. Parecer prévio favorável do agente UI/UX e Product Design.

Inventário atual:50 instâncias de `x-tabela` em41 views/partials. Uma tabela adicional na página pública inicial é uma ilustração com três alunos fictícios, não uma coleção operacional; manter sua composição publicitária.

| Parte | Entrega e situação inicial |
|---|---|
| Contrato compartilhado | Aprovado previamente: superfície principal única, alinhamento e separação consistentes |
| Componente | Props explícitas para integração e inset24px desktop/16px celular; sem inferência por ancestral genérico |
| Migração das50 instâncias | Aplicada por família; respeita card-pad existente |
| Listas no celular | Cards com título, dados e todas as ações dentro da superfície principal |
| Compacta/matriz/seleção | Preservar comportamentos próprios; duas colunas não são impostas às demais tabelas |
| Avisos compartilhados | Padrão do PR178: margens antes/depois, sem somar gap dos containers explícitos |
| Testes e revisão visual | Pendentes; amostras desktop320/390 e estados vazio/erro/ações |
| Publicação | Homologação no fechamento integral DC1; produção depende de decisão posterior |

Implementar no próprio `x-tabela` integração estrutural com contorno suave, sem criar outro card. Para tabelas antes full-bleed dentro de card sem padding, declarar inset explicitamente; nos card-pad, detalhes, gráficos e diálogos, não acrescentar outro padding. Remover a anulação da superfície mobile somente nas três listagens que usam tabela-superficie (Alunos, Turmas e Provas).

Preservar IDs, rotas, CSRF, permissões, dados secundários, seleções e os elementos existentes. Coleções parciais continuam sem paginação/busca local; matrizes mantêm comparação e rolagem acessível; seleção em lote não perde inputs ao mudar página. Sem alteração de API, banco, critérios de visibilidade ou dados acadêmicos. Regras de impressão anulam contorno/padding novos para não prejudicar A4.

Validar listas Alunos/Turmas/Provas/Equipe, disciplina/área, amostra de relatório/matriz e seleção; confirmar que o card principal envolve os cards móveis e que as ações continuam acessíveis. Padrão do título e dos dados secundários deve preservar o conteúdo específico de cada tarefa, sem ocultação por conveniência visual.

## Componente compartilhado

| Prop | Padrão | Uso |
|---|---|---|
| integrada | true | Contorno discreto, toolbar/tabela/rodapé unidos; sem superfície de card adicional |
| interna | false | Wrapper estrutural com inset24/16 apenas nas19 instâncias identificadas sem padding do pai |
| modo | lista | Lista responsiva; compacta e matriz mantêm suas representações explícitas |
| completa | false | Habilitar controles locais somente quando toda a coleção está no HTML |

Permanecem os tokens `--surface`, `--border`, `--border-soft`, `--radius-md`, tipografia e cores semânticas do projeto. A mudança não cria escala visual paralela. No celular, o modo lista continua usando uma única árvore de dados transformada em cards, sem duplicação de formulários ou elementos acessíveis; o modo compacta permanece tabela. A legenda acessível identifica a coleção; cabeçalhos e rótulos continuam associados aos dados. Matrizes mantêm região com foco e rolagem; botões preservam alvo mínimo44px. Sem JavaScript, registros e ações continuam no HTML; erro de consulta não deve parecer lista vazia. Impressão remove apenas o inset/contorno novos.

Migração explícita aplicada a50 instâncias. Das19 com inset, cinco são painéis administrativos, três são listagens principais, três aparecem no detalhe da turma/disciplinas da turma, cinco estão na gestão de disciplinas/áreas, duas em relatórios e uma no atendimento de instituições. A seção de histórico de exceções funcionais, antes sem superfície, passa a utilizar o próprio card-pad. As demais mantêm o padding existente, incluindo quatro tabelas de escolas e a de pagamentos. A tabela ilustrativa pública permanece fora desse contrato operacional.

Refinamento após revisão das primeiras capturas: Alunos, Provas e Turmas passam a reunir filtros GET e tabela em um único card-pad. Retirar `interna=true` dessas três instâncias, reduzindo o total final de insets adicionais a16; o único padding vem do card principal. Busca e selects ganham rótulos acessíveis, e os filtros permanecem disponíveis nos estados vazio/erro. Remover a antiga regra que tornava transparente a superfície dessas três listas no celular. Não envolver os demais formulários de ação em um formulário externo.

A suíte web completa passou178 testes/1698 asserções após a migração inicial das50 tabelas. O refinamento das três listas exige nova conferência dos testes pertinentes e das capturas finais. No Chrome,25 alunos exclusivamente fictícios confirmaram paginação para21–25 e preservação de25 ações; catálogo de disciplinas paginado no servidor permanece sem paginação local. Produção e homologação acessível não foram alteradas.
