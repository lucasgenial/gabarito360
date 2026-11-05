# DC1 — organizar disciplinas a partir da área do conhecimento

04/11/2026. Revisão antes da implementação após o responsável preferir que a área receba disciplinas. A relação no banco continua N:N; muda a entrada principal da tarefa. O agente UI/UX e Product Design aprovou previamente esta composição incremental.

- Área: nome, sigla/situação, tabela “Disciplinas desta área (opcional)” com checkboxes e campos nome/sigla/situação. No celular, cards completos com seleção acessível e alvo44px. Associar não duplica nem transfere a disciplina.
- Disciplina: cadastro independente sem seletor de áreas concorrente. Detalhe mantém a leitura das áreas associadas. Organização ocorre a partir da área; orientações extensas permanecem em `Saiba mais +` nativo, fechado inicialmente em todos os tamanhos.
- A área pode ficar sem disciplinas. Opções são o catálogo completo autorizado da escola; não usar a primeira página como catálogo inteiro, nem seleção automática por referência ou nome. Disciplinas inativas já associadas são preservadas; novas associações exigem disciplina e área ativas. Nenhuma permissão docente é concedida por classificação.
- Salvar as relações junto com a área, sob transação e trava do espaço. Alterações por clientes anteriores que enviem `area_ids` na disciplina continuam explícitas, autorizadas e auditadas. Omissão não significa retirada. Atualizar as versões de ambos os lados quando muda a associação, evitando sobrescrita concorrente entre as duas entradas.
- Preservar histórico append-only e todos os dados acadêmicos. Somente relações retiradas explicitamente são desassociadas; disciplinas/áreas não são apagadas. Sem migração adicional e sem GII.
- Remover Cancelar do cabeçalho apenas nas telas que já têm Cancelar equivalente no fim do formulário. Inventário em todas as views encontrou duplicidade em `disciplinas/form` e `disciplinas/areas/form`; demais ocorrências únicas, Voltar, Fechar e cancelamento de operação/assinatura permanecem.

| Parte | Situação inicial |
|---|---|
| Direção independente | Aprovada antes do código |
| Área como entrada principal e tabela/cards | Em implementação |
| Concorrência/auditoria das duas rotas | Em implementação |
| Cancelar duplicado | Duas telas identificadas para correção |
| Testes e revisão visual atualizada | Pendente |
| Integração de provas e perfis de teste | Próxima parte; autorizada, ainda pendente |
| Publicação | Apenas DC1 completo na homologação; sem produção |

Testar área com zero/várias disciplinas, disciplina em duas áreas, edição parcial, inativa preservada, remoção explícita, isolamento por espaço, concorrência área↔disciplina e recuperação409, além de desktop320/390px e ausência de Cancelar duplicado no cabeçalho. A aprovação das telas anteriores não conclui esta revisão.

Refinamento antes da implementação dos controles: o responsável apontou que a seleção não seguia o padrão completo das tabelas. Estender o próprio `x-tabela` com modo explícito `selecao`, apenas para coleção integral: busca por nome/sigla, ordenação, registros por página, paginação, filtro explícito Somente selecionadas e contadores globais. Manter os mesmos elementos/inputs no DOM, inclusive nas páginas ocultas. Desmarcar sob filtro de selecionadas não deve ocultar a linha em foco; reaplicar o filtro por ação deliberada. Preservar o guard de tabelas legadas com checkboxes e das coleções paginadas no servidor. Parecer prévio do especialista aprovado.

O responsável também rejeitou o card Dados da área. Remover essa caixa redundante, colocando sigla/situação compactas no subtítulo e priorizando a tabela das disciplinas. Essa composição exige nova evidência visual.

Evidências iniciais:21 testes API/237 asserções em SQLite e MariaDB, incluindo versões nos dois sentidos, inativa, escopo e relações opcionais;16 testes web/202 asserções após a extensão das tabelas, antes da asserção adicional do detalhe sem card. Chrome local com API real e35 disciplinas fictícias confirmou seleção após busca, navegação página2, ordenação por sigla, filtro de selecionadas sem sumir a linha/foco ao desmarcar, filtro vazio/limpeza e salvamento/reabertura das5 seleções inclusive em páginas ocultas. As8 tabelas acadêmicas do QA local foram preservadas ao preparar o volume. Nenhum dado do servidor foi alterado. Revisão visual atualizada e CI ainda pendentes.
