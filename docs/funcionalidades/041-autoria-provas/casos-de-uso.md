# 041 — Autoria das provas e "Meu espaço" — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Vice-diretor; todos os usuários autenticados ("Meu espaço") — ver [docs/03](../../03-perfis-e-permissoes.md)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

---

## UC-041-01 — Professor consulta apenas as provas que lhe cabem

**Ator:** Professor (vínculo de escola)
**Permissão:** `prova.ver`
**Escopo:** turma
**Pré-condições:** vínculo Professor selecionado.
**Pós-condições:** nenhuma.
**Tela/rota:** web `GET /provas`; API `GET /v1/provas` (e todas as rotas que usam `Escopo::provas`)

**Fluxo principal:**
1. A lista traz as provas criadas pelo próprio professor nesse perfil.
2. Traz também as provas criadas pela coordenação ou direção que estejam ligadas a uma turma atribuída a ele (com DC1: disciplina indicada e atribuição vigente).

**Fluxos alternativos e de erro:**
- 2a. Outro professor da mesma turma criou a prova → não aparece.
- 2b. O professor perde a turma → a prova institucional some da sua lista; a gestão continua vendo.
- 2c. A mesma pessoa criou a prova como Coordenadora ou Diretora → como Professora, só vê se a turma lhe estiver atribuída.

**Regras de negócio:** docs/132 — selecionar Professor não soma poderes de gestão.

**Critérios de aceite:**
- [ ] Dois professores na mesma turma não compartilham provas.
- [ ] A regra vale para lista, painel, gabarito, resultados, relatórios e cartões.

**Situação:** Implementado — evidência: `apps/api/app/Support/Escopo.php` (`consultarProvas`); teste `ProvasAutoriaTest`; PR #162.

---

## UC-041-02 — Professor não edita nem copia prova institucional

**Ator:** Professor
**Permissão:** `prova.editar`, `gabarito.editar`
**Escopo:** turma
**Pré-condições:** prova institucional visível ao professor.
**Pós-condições:** nenhuma alteração.
**Tela/rota:** rotas de edição de prova e gabarito

**Fluxo principal:**
1. O professor abre a prova institucional e a consulta.
2. As ações de editar, publicar, excluir e copiar não são oferecidas; chamadas diretas respondem 404.

**Critérios de aceite:**
- [ ] Zero provas institucionais editáveis no vínculo Professor, mesmo quando a mesma conta as criou na gestão.

**Situação:** Implementado — evidência: `Escopo::podeEditarProva`, `provaEditavel`; conferência registrada no docs/132.

---

## UC-041-03 — Gestão da escola consulta as provas de todos os professores

**Ator:** Coordenador, Diretor Escolar, Vice-diretor
**Permissão:** `prova.ver`
**Escopo:** escola
**Pré-condições:** vínculo de gestão selecionado.
**Pós-condições:** nenhuma.
**Tela/rota:** web `GET /provas`; API `GET /v1/provas`

**Fluxo principal:**
1. A lista traz todas as provas da escola, de qualquer autor.

**Critérios de aceite:**
- [ ] Nenhuma prova de outra instituição aparece.

**Situação:** Implementado — evidência: `Escopo::consultarProvas`.

---

## UC-041-04 — Professor autônomo consulta as próprias provas

**Ator:** Professor autônomo
**Permissão:** `prova.ver`
**Escopo:** espaço pessoal
**Pré-condições:** espaço pessoal selecionado.
**Pós-condições:** nenhuma.
**Tela/rota:** web `GET /provas`; API `GET /v1/provas`

**Fluxo principal:**
1. A lista traz só as provas criadas por ele no espaço pessoal.

**Critérios de aceite:**
- [ ] Nenhum acesso institucional é sugerido.

**Situação:** Implementado — evidência: `Escopo::consultarProvas` (`professor_autonomo`).

---

## UC-041-05 — Ver quem criou a prova

**Ator:** perfis com `prova.ver`
**Permissão:** `prova.ver`
**Escopo:** escola | turma | espaço pessoal
**Pré-condições:** prova visível.
**Pós-condições:** nenhuma.
**Tela/rota:** `provas/index` (card/lista), `provas/show`

**Fluxo principal:**
1. Lista e detalhe mostram "Criada por: nome".

**Fluxos alternativos e de erro:**
- 1a. Sem autoria registrada → "Autoria não registrada".

**Critérios de aceite:**
- [ ] Nome escapado; sem CPF, e-mail ou dados da conta.
- [ ] Procedência de cópia aparece separada do criador ([FN-042](../042-duplicacao-provas/casos-de-uso.md)).

**Situação:** Implementado — evidência: `provas/index.blade.php`, `provas/show.blade.php`; PR #162.

---

## UC-041-06 — Consultar "Meu espaço"

**Ator:** qualquer usuário autenticado
**Permissão:** nenhuma específica
**Escopo:** vínculo ativo
**Pré-condições:** sessão com vínculo selecionado.
**Pós-condições:** nenhuma (abrir o bloco não troca o vínculo).
**Tela/rota:** layout compartilhado; `components/contexto-ativo.blade.php`

**Fluxo principal:**
1. O bloco "Meu espaço" aparece fechado.
2. Aberto, mostra Minha escola e Meu perfil; no espaço pessoal, Tipo de espaço: Pessoal e Meu perfil.

**Critérios de aceite:**
- [ ] Funciona por teclado, com foco visível, nos temas claro e escuro, em 320 px.

**Situação:** Implementado — evidência: `contexto-ativo.blade.php`; PR #162.

---

## UC-041-07 — Trocar de vínculo pelo ícone da barra superior

**Ator:** usuário com mais de um acesso
**Permissão:** nenhuma específica (revalidada pela API)
**Escopo:** conta
**Pré-condições:** existe acesso alternativo ao atual.
**Pós-condições:** nova sessão contextual; a outra aba continua válida.
**Tela/rota:** ícone "Trocar vínculo"/"Trocar acesso" e modal; endpoints de seleção de FN-015

**Fluxo principal:**
1. O usuário clica no ícone; o modal lista os acessos, com "Atual" sem ação e "Entrar" nos demais.
2. Escolhe um acesso; a API revalida e devolve a URL com o novo handle.

**Fluxos alternativos e de erro:**
- 1a. Só o acesso atual → nem ícone nem modal.
- 2a. Sem resposta em 20 s, rede ou 5xx → mensagem de confirmação incerta; controles restaurados; sem repetição automática.
- 2b. Erro confirmado → mensagem no modal.

**Critérios de aceite:**
- [ ] Nenhum token chega à página.
- [ ] Escape/Fechar devolvem o foco ao ícone.

**Situação:** Implementado — evidência: docs/132 (testes web e revisão independente); PR #162. ⚠ Validação do responsável e produção pendentes (subfases 2.1 e 2.2).
