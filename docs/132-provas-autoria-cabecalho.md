# Autoria das provas e contexto compartilhado

## Planejamento revisado — 03/11/2026

Correção prioritária anterior a ID1. A vinculação de dois professores à mesma turma não compartilha suas provas. Professor institucional consulta suas próprias entradas e provas criadas pela coordenação/direção somente quando vinculadas a uma turma atribuída a ele. Não pode copiar as provas institucionais, nem mesmo se a mesma conta as criou em um vínculo de gestão. Professor independente consulta suas próprias entradas no espaço pessoal. Coordenador e diretor escolar mantêm acesso às provas e turmas de todos os professores de sua escola. Nenhuma dessas funções concede acesso a outra instituição. A cópia para colega continua CP2, sem antecipação neste pacote.

Não mudar autoria dos registros existentes para fazer o botão aparecer. A mesma consulta central deve proteger listagem, painel, turma, gabarito, resultados, relatórios, cartões e operações diretas. O nome do criador da entrada é projetado na API e mostrado como **Criada por** em lista/card e detalhe; sem expor CPF, e-mail ou conta serializada. A procedência da cópia permanece distinta do criador da nova entrada.

O usuário acrescentou a padronização do vínculo e refinou o desenho durante a implementação: instituição e perfil ficam **fora da barra superior**. No desktop, card Minha escola/Meu perfil no menu lateral é clicável para trocar o vínculo; com o menu recolhido, o mesmo card aparece acima do conteúdo. No celular, informações ficam acima do conteúdo e a troca usa ícone de44px no cabeçalho. Não duplicar ação de troca no topo do desktop. Aplicação pelo layout compartilhado, cobrindo contexto institucional, pessoal e interno com rótulos próprios. Nome completo com quebra natural, nunca ocultado somente dentro de Mais. Renderizações do componente são mutuamente exclusivas porCSS e semIDs repetidos. Menu da conta com botão semântico, estado expandido, Escape e retorno de foco.

| Entrega | Critério | Situação ao preparar implementação |
|---|---|---|
| A — escopo por autoria | Dois professores na mesma turma não compartilham provas; coordenação/direção mantêm visão da escola | Em validação |
| B — Criada por | Nome escapado em lista/card/detalhe; conta não serializada | Em validação |
| C — cabeçalho compartilhado | Desktop e celular; troca de vínculo, perfil, tema e conta preservados | Em revisão independente |
| D — integração e homologação | CI, revisão visual, backup, comparação acadêmica; produção inalterada | Pendente |
| ID1 — identidade | Próxima etapa principal, fora deste pacote | Planejada |

Migration aditiva: perfil_criacao registra a função usada na criação e não acompanha mudanças futuras do perfil da pessoa; não é aceito do formulário. Não inferir essa função histórica para todo o legado. As quatro provas existentes confirmadas pelo responsável como criadas pela coordenação serão conciliadas explicitamente, com recibo e conferência do autor, sem trocar criado_por. O comparador do deploy desconsidera somente essa coluna nova ao confrontar os conteúdos acadêmicos anteriores; todos os campos anteriores e todas as nove tabelas continuam comparados. Publicar o pacote concluído somente na homologação. Promoção da produção continua dependente de decisão expressa posterior.
