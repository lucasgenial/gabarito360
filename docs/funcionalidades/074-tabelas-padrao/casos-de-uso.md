# 074 — Padrão de tabelas e listas — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos os perfis que consultam listas, relatórios e matrizes (ver docs/03 (`docs/03-perfis-e-permissoes.md`))
**Atualizado em:** 2026-11-09

Funcionalidade transversal de apresentação. As permissões e o escopo de cada lista são os da tela dona; o componente não acrescenta nem remove dados. Fluxos de negócio relacionados: UC-004, UC-006 e UC-008 de docs/07 (`docs/07-casos-de-uso.md`).

---

## UC-074-01 — Consultar uma lista no computador

**Ator:** qualquer perfil com acesso à lista
**Permissão:** a da tela (por exemplo `turma.ver`, `aluno.ver`)
**Escopo:** o da tela
**Pré-condições:** lista renderizada com `x-tabela` modo `lista`.
**Pós-condições:** registros e ações visíveis dentro do card principal.
**Tela/rota:** por exemplo `alunos/index`, `provas/index`, `turmas/index`, `membros/index`.

**Fluxo principal:**
1. O usuário abre a lista; filtros e tabela aparecem no mesmo card (Alunos, Provas, Turmas).
2. Se a coleção inteira está no HTML (`completa=true`), escolhe 20/50/100/Todos e vê "X–Y de N registros carregados".
3. Ordena pelo seletor (ordem original, A–Z, Z–A com `Intl.Collator` pt-BR) ou pelos cabeçalhos (TW3, FN-075).

**Fluxos alternativos e de erro:**
- 2a. Coleção paginada no servidor (clientes, catálogo, cópias): usa o paginador do servidor; não há busca nem ordenação local sobre a página parcial.
- 2b. Lista vazia ou erro da API: estados distintos, com os filtros ainda disponíveis.

**Regras de negócio:** docs/131 ("Diagnóstico comum e padrão proposto"); AGENTS.md "Tabelas, cards de registros e avisos".

**Critérios de aceite:**
- [x] Mudar tamanho ou ordem volta à página 1 e mantém o foco útil.
- [x] Nenhum controle local quando a coleção é parcial.
- [x] Erro não aparece como lista vazia.

**Situação:** Implementado — evidência: `components/tabela.blade.php`, `public/js/tabelas.js`, `tests/Feature/TabelasWebTest.php`; PR #161, #186.

---

## UC-074-02 — Consultar uma lista no celular em cards

**Ator:** qualquer perfil com acesso à lista
**Permissão:** a da tela
**Escopo:** o da tela
**Pré-condições:** largura de celular (320–390 px de referência).
**Pós-condições:** cada registro vira um card com título, dados e todas as ações autorizadas.
**Tela/rota:** as mesmas do UC-074-01.

**Fluxo principal:**
1. A mesma árvore HTML é apresentada como cards (sem duplicar formulários, IDs ou seleção).
2. O título do card é a coluna principal declarada, não inferida pela posição.
3. As ações ficam no rodapé do card.

**Fluxos alternativos e de erro:**
- 1a. Modo `compacta`: permanece tabela.
- 1b. Modo `matriz`: não vira card (ver UC-074-03).

**Critérios de aceite:**
- [x] Nenhum botão, link ou campo perdido em relação ao desktop.
- [x] Card principal continua visível no celular.

**Situação:** Implementado — evidência: PR #161, #186; docs/149 ("Validação final").

---

## UC-074-03 — Comparar resultados numa matriz

**Ator:** Professor, Coordenador, Diretor Escolar, Diretor de Núcleo, Administrador Rede
**Permissão:** `resultado.ver` ou a da tela de relatório/correção
**Escopo:** turma, escola, núcleo ou rede conforme o relatório
**Pré-condições:** relatório com matriz (alunos × provas ou questões).
**Pós-condições:** comparação preservada em qualquer largura.
**Tela/rota:** `relatorios/turma`, `relatorios/consolidado`, `relatorios/_analise-questoes`, `correcao/turma`.

**Fluxo principal:**
1. A matriz aparece num quadro com rolagem horizontal própria, com rótulo de região.
2. No teclado, o usuário seleciona o quadro e usa as setas.

**Fluxos alternativos e de erro:**
- 1a. Impressão: regras de impressão anulam contorno e padding novos; A4 preservado.

**Critérios de aceite:**
- [x] Cabeçalhos completos e links por célula preservados.
- [ ] ⚠ Resumo alternativo em cards por aluno — Adiado (subfase 1.3a, TBL1-C).

**Situação:** Parcial — evidência: `x-tabela modo="matriz"` nas views citadas; docs/131.

---

## UC-074-04 — Selecionar itens em lote

**Ator:** perfis com permissão de gestão na tela (por exemplo disciplinas da escola)
**Permissão:** a da tela (por exemplo `disciplina.gerir`)
**Escopo:** o da tela
**Pré-condições:** `x-tabela modo="selecao"` com coleção completa.
**Pós-condições:** seleção preservada entre páginas e filtros.
**Tela/rota:** por exemplo `disciplinas/areas/show`, `escolas/form`.

**Fluxo principal:**
1. O usuário busca por nome ou sigla e aciona "Filtrar".
2. Marca itens; pode ver "Somente selecionadas".
3. Ao mudar a seleção, recebe aviso para refiltrar.

**Fluxos alternativos e de erro:**
- 1a. Nenhum item no filtro: mensagem "Nenhuma disciplina corresponde ao filtro. As seleções fora do filtro continuam preservadas." e botão "Limpar filtros".

**Critérios de aceite:**
- [x] Inputs marcados não se perdem ao mudar de página.

**Situação:** Implementado — evidência: `components/tabela.blade.php` (bloco `selecao`).

---

## UC-074-05 — Listar com paginação no servidor (contrato opcional)

**Ator:** qualquer perfil com acesso a listas grandes
**Permissão:** a da lista
**Escopo:** o da lista, aplicado por `Escopo` na API
**Pré-condições:** rota da API com `paginado=1`.
**Pós-condições:** página com total do recorte, ordenação estável e desempate por ID.
**Tela/rota:** a definir por lista; endpoints da API existentes com parâmetro opt-in.

**Fluxo principal (proposto):**
1. A Web pede `paginado=1` com página, tamanho e ordenação de uma whitelist.
2. A API filtra e ordena no banco, devolve página e total.
3. A tabela mostra o alcance e a navegação sem prometer busca global local.

**Fluxos alternativos e de erro:**
- 1a. Consumidores sem `paginado=1` continuam recebendo a resposta integral.
- 2a. Erro da API: estado de erro, nunca lista vazia.

**Regras de negócio:** docs/131 "Contratos de dados e segurança"; ADR-195 item 4.

**Critérios de aceite:**
- [ ] Contrato compatível com consumidores atuais.
- [ ] Medição com o padrão de 1000 usuários simultâneos (FN-077).

**Situação:** Não implementado — nenhuma ocorrência de `paginado` em `apps/api/app` ou `apps/web/app`; subfase 3.1.
