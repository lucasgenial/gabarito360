# 074 — Padrão de tabelas e listas — Planejamento

**Implementação:** Em homologação
**Etapa:** Em homologação
**Prioridade no plano mestre:** trilha contínua de qualidade (§5), aplicada dentro das entregas; sem posição própria na fila. Pendência na trilha: paginação opcional na API.
**Natureza:** existente documentada a partir do código
**Origem:** ramo do CI1-F ampliado pelo responsável para todas as tabelas ([docs/130](../../130-ci1-f-validacao-integrada.md), [docs/131](../../131-padronizacao-tabelas-ui-ux.md)); TBL2 surgiu na entrega DC1 ([docs/149](../../149-tbl2-tabelas-integradas.md))
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Padronizar todas as tabelas operacionais da área logada num componente único, que vira cards no celular sem perder dados, ações nem permissões, integrado à superfície do card principal. Atende todos os perfis que consultam listas, relatórios e matrizes.

## 2. Escopo
- **Inclui:**
  - componente Blade `x-tabela` (`apps/web/resources/views/components/tabela.blade.php`) com os modos `lista`, `selecao`, `compacta` e `matriz` e as props `integrada`, `interna`, `completa`;
  - controles locais (20/50/100/Todos, ordenação pt-BR, alcance "X–Y de N") só quando a coleção inteira está no HTML;
  - cards no celular a partir da mesma árvore de dados (sem duplicar formulários ou IDs);
  - matrizes com rolagem própria, região focável e instrução de teclado;
  - integração ao card principal (TBL2) e margens de avisos `x-aviso`;
  - contrato opcional de paginação na API (`paginado=1`), pendente.
- **Não inclui:**
  - coluna Ações à direita e ordenação pelos cabeçalhos: TW3 de [FN-075](../075-tailwind-ui/) (PR #225);
  - migração visual das famílias de tabelas para Tailwind: TW3 restante, [FN-075](../075-tailwind-ui/);
  - carregamento regional das listas: [FN-076](../076-carregamento-estados/);
  - busca nacional paginada do catálogo: [FN-032](../032-busca-catalogo-autocompletar/).

## 3. Fontes de verdade
- Mockups com tabelas: `mockups/dashboard-*.html`, `escolas.html` e demais listados em [docs/01](../../01-inventario-dos-mockups.md).
- Documentos de origem: [docs/130](../../130-ci1-f-validacao-integrada.md), [docs/131](../../131-padronizacao-tabelas-ui-ux.md), [docs/149](../../149-tbl2-tabelas-integradas.md); regra permanente em `AGENTS.md` ("Tabelas, cards de registros e avisos").
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Perfis: [docs/03](../../03-perfis-e-permissoes.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-073 layout-responsivo | Componentes e régua de larguras | Concluída |
| FN-038 (DC1) | TBL2 foi publicada no fechamento de DC1 | Em homologação (PR #186) |

**Funcionalidades que dependem desta:** FN-075 (TW3 converte as famílias de tabelas sobre o `x-tabela`), todas as telas com listas (cadastros escolares, provas, correção, relatórios, painéis). FN-032 e futuras listas grandes aguardam o contrato de paginação na API.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (TBL1-A) | Componente `x-tabela` e as 4 listas principais (provas, alunos, turmas, equipe) | Cabeçalho, filtros, total e paginação honestos; cards preservam campos e ações | Entregue (homologação) | PR #161; docs/131 |
| 1.2 (TBL1-B) | Clientes, catálogo, pedidos e seleção em lote | Seleção e alcance explícitos; nenhuma ação perdida entre páginas | Entregue (homologação) | PR #161; docs/131 |
| 1.3 (TBL1-C) | Relatórios, matrizes, correção e impressão | Matrizes com rolagem e teclado; impressão A4 | Entregue (homologação), exceto o resumo alternativo em cards das matrizes | PR #161; docs/131 |
| 1.3a (TBL1-C) | Resumo alternativo em cards das matrizes, por aluno | — | Adiada | docs/131 |
| 1.4 (TBL1-D) | Painéis, assinatura e convergência das 41 instâncias | Todas as instâncias rastreadas e revisadas | Entregue (homologação) | PR #161; docs/131 |
| 2.1 (TBL2) | Tabelas integradas à superfície principal (50 instâncias em 41 views); filtros e tabela no mesmo card em Alunos, Provas e Turmas | Revisão UI/UX aprovada; testes web | Entregue (homologação) | PR #186; docs/149 |
| 3.1 | Paginação opcional na API (`paginado=1`) | Whitelist de ordenação e tamanho, total filtrado, desempate por ID, `Escopo` preservado, erro distinto de lista vazia | Pendente | docs/131 ("Contratos de dados e segurança") |

## 6. Contrato técnico
- Hoje sem mudança de API: o componente só apresenta o que a tela já recebe. Coleções parciais (paginadas no servidor: clientes, catálogo, cópias) mantêm os paginadores do servidor e não ganham busca/ordenação local que prometa cobrir o total.
- Subfase 3.1 (pendente): contrato opt-in `paginado=1` nas rotas de listas com potencial de crescimento, preservando a resposta integral dos consumidores atuais (selects). Seguir a ADR-195 (leitura proporcional) e medir com o padrão de 1000 usuários simultâneos (FN-077). Pelo docs/200, as listas escolares atuais ficam abaixo de centenas de linhas e não há gargalo comprovado; paginar sem necessidade quebra consumidores.
- Testes: `apps/web/tests/Feature/TabelasWebTest.php`.
- Inventário atual no código: 53 usos de `<x-tabela` em 45 arquivos de view (o plano mestre registra 50 instâncias/41 views na entrega TBL2; a diferença vem de telas posteriores).

## 7. Interface
- Desktop: tabela com contorno discreto dentro do card principal; inset 24 px (desktop) / 16 px (celular) só quando `interna=true`.
- Celular: cards com título, dados secundários e todas as ações autorizadas; o card principal continua visível.
- Matrizes: quadro comparativo com rolagem horizontal própria, `role=region`, `tabindex=0` e dica de teclado.
- Estados vazio e erro distintos; filtros disponíveis nos dois estados.
- Conferência registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Resumo alternativo em cards das matrizes (TBL1-C) | Retomar / manter adiado | Manter adiado até haver pedido de uso no celular; a comparação entre provas é preservada | Adiada (docs/131) |
| Paginação opcional na API | Implementar agora / quando houver volume | Implementar junto com a primeira lista que ultrapassar centenas de linhas, medida no padrão de 1000 usuários | Pendente |
| Publicação de TBL1 na homologação registrada como Incerta no inventário (validação no clone 9120; recibo "no PR") | — | Considerar coberta pela publicação de TBL2 (PR #186), que inclui o componente | Registro |
| Produção | — | Segue a fila do §7 do plano mestre (bloco "Tabelas") e a decisão §4 item 2 | Pendente (§4 item 2) |

Decisões do §4 que tocam esta funcionalidade: item 2 (gate de produção e fila acumulada).

## 9. Ramos desta entrega
Nenhum ramo aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
