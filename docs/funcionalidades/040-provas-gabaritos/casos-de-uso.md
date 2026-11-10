# 040 — Provas e gabaritos — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Professor, Coordenador, Professor autônomo, Administrador da Rede (criação e edição); Diretor Escolar por extra; Diretor de Núcleo, Vice-diretor e Aplicador (consulta restrita) — ver docs/03 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

O fluxo de ponta a ponta está no UC-003 do docs/07 (`docs/07-casos-de-uso.md`) (Criar Prova e Gabarito); os casos abaixo detalham o comportamento atual sem repeti-lo.

---

## UC-040-01 — Listar e consultar provas

**Ator:** perfis com `prova.ver`
**Permissão:** `prova.ver`
**Escopo:** escola | turma | espaço pessoal (regras de autoria em [FN-041](../041-autoria-provas/casos-de-uso.md))
**Pré-condições:** autenticado com vínculo ativo.
**Pós-condições:** nenhuma.
**Tela/rota:** `provas.html`; web `GET /provas`, `GET /provas/{id}`; API `GET /v1/provas`, `GET /v1/provas/{id}`

**Fluxo principal:**
1. A lista mostra as provas do escopo, com busca, status e progresso da correção.
2. O detalhe mostra dados, turmas, autoria e ações permitidas.

**Fluxos alternativos e de erro:**
- 2a. Prova fora do escopo → 404 "Prova não encontrada."
- 2b. Aplicador vê só provas com aplicação no dia e já publicadas, sem conteúdo de gabarito.

**Critérios de aceite:**
- [ ] Nenhuma prova de outra escola ou espaço aparece.

**Situação:** Implementado — evidência: `apps/api/app/Http/Controllers/Api/ProvaController.php` (`index`, `show`), `App\Support\Escopo::provas`.

---

## UC-040-02 — Criar prova (passo Dados)

**Ator:** Professor, Coordenador, Professor autônomo, Administrador da Rede
**Permissão:** `prova.criar`
**Escopo:** escola | espaço pessoal
**Pré-condições:** escola e turmas no escopo; turmas ativas.
**Pós-condições:** prova em `rascunho`, com `criado_por`, perfil e vínculo de criação registrados, e turmas associadas.
**Tela/rota:** `criar-prova.html`; web `GET /provas/nova`, `POST /provas`; API `POST /v1/provas`

**Fluxo principal:**
1. O ator informa título (até 255), disciplina (texto até 100, ou `disciplina_ids` quando o DC1 se aplica — [UC-038-06](../038-disciplinas-curriculo/casos-de-uso.md)), série, número de questões (1–100) e de alternativas (3, 4 ou 5).
2. Opcionais: nota máxima (0,5–100), tipo de pontuação (`igual`, `personalizado`), anular se todas marcadas, opção de língua, gerar cartão em PDF, data de aplicação, turmas.
3. A API grava o rascunho e segue para o gabarito.

**Fluxos alternativos e de erro:**
- 1a. Escola fora do escopo → 404 (com espaços) ou 403 "Escola fora do seu escopo."
- 1b. Turma fora do escopo ou de outra escola → 404 "Turma não encontrada." ou 403 "Turma fora do seu escopo ou de outra escola."
- 2a. Turma arquivada → 422 "Desarquive a turma antes de criar uma nova associação."
- 2b. No espaço pessoal, `escola_id` é proibido.

**Regras de negócio:** docs/06; docs/03 (diretor não cria prova por padrão). `formato_avaliacao` aceita só `gabarito`.

**Critérios de aceite:**
- [ ] Prova nasce como rascunho.
- [ ] Autoria não é aceita do formulário.

**Situação:** Implementado — evidência: `ProvaController::store`; commit 2546b1b. ⚠ O stepper de três passos consta como "Incerto" no plano mestre; o código exibe "Dados → Gabarito oficial → Cartão e turmas" em `provas/create.blade.php` (subfase 1.3, a confirmar).

---

## UC-040-03 — Editar prova e associar turmas

**Ator:** quem pode editar a prova (autor ou gestão da escola)
**Permissão:** `prova.editar`
**Escopo:** escola | espaço pessoal
**Pré-condições:** prova editável pelo ator (`Escopo::provaEditavel`).
**Pós-condições:** dados e turmas atualizados.
**Tela/rota:** web `PUT /provas/{id}`; API `PUT /v1/provas/{id}`

**Fluxo principal:**
1. O ator altera os campos do cadastro e a lista de turmas e salva.

**Fluxos alternativos e de erro:**
- 1a. Prova não editável pelo ator (por exemplo, professor diante de prova institucional) → 404.
- 1b. Mudar o status de uma cópia por edição → 422 "Use a publicação do gabarito para publicar esta cópia."

**Critérios de aceite:**
- [ ] Professor não edita prova criada pela gestão.

**Situação:** Implementado — evidência: `ProvaController::update`.

---

## UC-040-04 — Preencher e salvar o gabarito

**Ator:** quem pode editar a prova
**Permissão:** `gabarito.editar` (consulta: `gabarito.ver`)
**Escopo:** escola | espaço pessoal
**Pré-condições:** gabarito ainda não publicado.
**Pós-condições:** questões gravadas; prova continua em rascunho.
**Tela/rota:** `gabarito.html`; web `GET /provas/{id}/gabarito`, `GET /provas/{id}/gabarito/editar`, `POST /provas/{id}/gabarito`; API `GET/POST /v1/provas/{id}/gabarito`

**Fluxo principal:**
1. O ator marca a alternativa correta de cada questão (A–E); pode informar alternativa de espanhol, peso e anulação.
2. Salva o rascunho, mesmo parcial.

**Fluxos alternativos e de erro:**
- 2a. Gabarito já publicado → 422 "Gabarito já publicado. Não pode ser editado."
- 2b. Falha ao gravar → 500 "Erro ao salvar gabarito."

**Critérios de aceite:**
- [ ] Salvar parcial mantém a prova em rascunho.
- [ ] Número de questão único, entre 1 e 100.

**Situação:** Implementado — evidência: `apps/api/app/Http/Controllers/Api/GabaritoController.php` (`show`, `salvar`); commit ca2c408.

---

## UC-040-05 — Publicar o gabarito

**Ator:** quem pode editar a prova
**Permissão:** `gabarito.editar`
**Escopo:** escola | espaço pessoal
**Pré-condições:** gabarito com todas as questões preenchidas.
**Pós-condições:** gabarito com `publicado_por` e `publicado_em`; prova em `publicada`, pronta para cartões (FN-045).
**Tela/rota:** web `POST /provas/{id}/gabarito/publicar`; API `POST /v1/provas/{id}/gabarito/publicar`

**Fluxo principal:**
1. O ator clica em "Publicar gabarito"; a API revalida a prova e o currículo e publica.

**Fluxos alternativos e de erro:**
- 1a. Sem gabarito → 422 "Gabarito não preenchido. Preencha o gabarito antes de publicar."
- 1b. Incompleto → 422 "Gabarito incompleto. Preencha todas as N questões antes de publicar."
- 1c. Já publicado → 422 "Gabarito já foi publicado."
- 1d. Cópia sem turmas selecionadas → 422 (ver [UC-042-03](../042-duplicacao-provas/casos-de-uso.md)).

**Critérios de aceite:**
- [ ] Gabarito publicado não é mais editável.

**Situação:** Implementado — evidência: `GabaritoController::publicar`.

---

## UC-040-06 — Publicar prova sem passar pelo gabarito e excluir rascunho

**Ator:** quem pode editar a prova
**Permissão:** `prova.editar`
**Escopo:** escola | espaço pessoal
**Pré-condições:** prova em rascunho.
**Pós-condições:** prova publicada, ou rascunho excluído.
**Tela/rota:** web `POST /provas/{id}/publicar`, `DELETE /provas/{id}`; API idem em `/v1`

**Fluxo principal:**
1. Publicar: muda o rascunho para publicada.
2. Excluir: remove o rascunho sem histórico.

**Fluxos alternativos e de erro:**
- 1a. Não é rascunho → 422 "Apenas rascunhos podem ser publicados."
- 1b. É cópia → 422 "Revise e publique o gabarito para publicar esta cópia."
- 2a. Não é rascunho → 422 "Apenas rascunhos podem ser excluídos."
- 2b. Rascunho com histórico → 409 "Esta prova possui histórico registrado e deve ser preservada."

**Critérios de aceite:**
- [ ] Prova publicada nunca é excluída.

**Situação:** Implementado — evidência: `ProvaController::publicar`, `destroy`.
