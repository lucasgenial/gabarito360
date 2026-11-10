# 038 — Disciplinas, áreas e currículo das provas (DC1) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Coordenador (gestão); Diretor Escolar, Vice-diretor, Professor (consulta); Professor e Coordenador (provas curriculares) — ver docs/03 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

Pré-condição comum: flag `DISCIPLINAS_ATIVAS` ligada, espaços ativos e vínculo de escola selecionado; caso contrário as rotas respondem 404.

---

## UC-038-01 — Manter o catálogo de disciplinas da escola

**Ator:** Coordenador
**Permissão:** `disciplina.gerir`
**Escopo:** escola
**Pré-condições:** vínculo de Coordenador ativo.
**Pós-condições:** disciplina criada/alterada com versão; áreas associadas.
**Tela/rota:** web `disciplinas.index|create|store|show|edit|update`; API `GET/POST /v1/disciplinas`, `GET/PUT /v1/disciplinas/{id}`, `GET /v1/disciplinas/opcoes`

**Fluxo principal:**
1. O ator cria a disciplina com nome (até 100), sigla (até 20), situação, áreas e, opcionalmente, a referência BNCC.
2. Na edição, envia a `versao` atual; a referência não pode ser trocada.

**Fluxos alternativos e de erro:**
- 1a. Outro perfil tenta gravar → 403 "A gestão de disciplinas cabe à coordenação autorizada."
- 1b. Referência inexistente → 422 "Referência não encontrada."
- 2a. Inativar com atribuições abertas → 422 "Encerre ou cancele as atribuições abertas antes de inativar a disciplina."
- 2b. Versão desatualizada → conflito.

**Critérios de aceite:**
- [ ] Diretor, vice e professor só consultam.
- [ ] Catálogo isolado por espaço.

**Situação:** Implementado — evidência: `DisciplinaController`, `App\Services\GestaoDisciplinas`; PR #171, #186.

---

## UC-038-02 — Indicar professores para uma disciplina

**Ator:** Coordenador
**Permissão:** `disciplina.gerir`
**Escopo:** escola
**Pré-condições:** disciplina ativa; professor com vínculo aceito.
**Pós-condições:** indicação ativa ou inativa com versão.
**Tela/rota:** web `disciplinas.indicar`; API `POST /v1/disciplinas/{id}/professores`, `GET /v1/disciplinas/professores`

**Fluxo principal:**
1. O ator escolhe o professor e marca ativo/inativo.

**Fluxos alternativos e de erro:**
- 1a. Disciplina inativa → 422 "Reative a disciplina antes de indicar professores."
- 1b. Desativar indicação com atribuição aberta → recusa.

**Critérios de aceite:**
- [ ] Indicação não concede acesso à turma por si só.

**Situação:** Implementado — evidência: `DisciplinaController::indicar`.

---

## UC-038-03 — Manter áreas do conhecimento e seus vínculos temporais

**Ator:** Coordenador
**Permissão:** `disciplina.gerir`
**Escopo:** escola
**Pré-condições:** —
**Pós-condições:** área criada/alterada; vínculos área–disciplina abertos ou encerrados com período.
**Tela/rota:** web `areas.*`; API `/v1/areas-conhecimento` (`opcoes`, lista, CRUD, `{id}/vinculos`, `{id}/desvincular`)

**Fluxo principal:**
1. O ator cria a área (nome, sigla, situação) e associa disciplinas com suas versões.
2. Para desvincular, informa o vínculo; se houver uso em prova, o sistema pede `confirmacao`.

**Fluxos alternativos e de erro:**
- 2a. Versão da disciplina ou da área desatualizada → conflito.

**Regras de negócio:** docs/148 — vínculo temporal não é período letivo.

**Critérios de aceite:**
- [ ] Desvincular com uso em prova exige confirmação explícita.
- [ ] Histórico de vínculos preservado.

**Situação:** Implementado — evidência: `AreaConhecimentoController`; PR #186.

---

## UC-038-04 — Adicionar disciplina à turma e gerir sua situação

**Ator:** Coordenador
**Permissão:** `disciplina.gerir`
**Escopo:** turma
**Pré-condições:** turma e disciplina ativas.
**Pós-condições:** componente da turma criado, inativado ou reativado.
**Tela/rota:** aba Disciplinas e professores; web `disciplinas.adicionar`, `disciplinas.situacao`; API `POST /v1/turmas/{turma}/disciplinas`, `POST .../{componente}/situacao`

**Fluxo principal:**
1. O ator busca a disciplina no catálogo (paginado) e adiciona à turma.
2. Pode inativar ou reativar o componente, informando a versão.

**Fluxos alternativos e de erro:**
- 1a. Turma ou disciplina inativa → 422 "Turma e disciplina precisam estar ativas."
- 2a. Reativar com catálogo inativo → 422 "Turma e catálogo precisam estar ativos para reativar esta disciplina."
- 2b. Inativar com atribuições abertas → 422 "Encerre ou cancele as atribuições abertas antes de inativar a disciplina nesta turma."

**Critérios de aceite:**
- [ ] Página parcial do catálogo não é apresentada como catálogo inteiro.

**Situação:** Implementado — evidência: `DisciplinaController::adicionar`, `situacao`.

---

## UC-038-05 — Registrar e encerrar atribuição docente com vigência

**Ator:** Coordenador
**Permissão:** `disciplina.gerir` + `turma.vincular_professor`
**Escopo:** turma
**Pré-condições:** componente ativo; professor indicado.
**Pós-condições:** atribuição titular, codocência ou substituição com início e fim (exclusivo); encerramento ou cancelamento com versão.
**Tela/rota:** `disciplinas.componente`, `disciplinas.atribuir`, `disciplinas.encerrar`; API `POST .../{componente}/atribuicoes`, `POST .../atribuicoes/{id}/encerrar`

**Fluxo principal:**
1. O ator escolhe professor, papel, início e fim opcional (posterior ao início) e confirma com chave UUID.
2. Para encerrar, escolhe "encerrar" ou "cancelar".

**Regras de negócio:** sem sobreposição de papel na mesma disciplina da turma (tu1-revisao-fluxos). A forma simplificada (disciplina + professor desde hoje) está em [UC-036-02](../036-organizacao-turma/casos-de-uso.md).

**Critérios de aceite:**
- [ ] Atribuição define a visibilidade institucional do professor ([FN-041](../041-autoria-provas/casos-de-uso.md)).
- [ ] Reenvio da mesma chave não duplica.

**Situação:** Implementado — evidência: `DisciplinaController::atribuir`, `encerrar`; PR #171.

---

## UC-038-06 — Configurar o currículo de uma prova

**Ator:** Professor, Coordenador (criadores de prova em vínculo de escola)
**Permissão:** `prova.criar` / `prova.editar`
**Escopo:** escola
**Pré-condições:** DC1 aplicável ao vínculo.
**Pós-condições:** prova ligada a disciplinas por ID (`prova_disciplinas`) e a turmas; texto "Configuração curricular" no campo legado.
**Tela/rota:** web `provas.preparar`, `provas.curriculo.edit|preparar|salvar`; API `GET|POST /v1/provas/opcoes-disciplinas`, `POST /v1/provas` com `disciplina_ids` e `turma_ids`

**Fluxo principal:**
1. O ator escolhe as turmas; o sistema oferece só as disciplinas comuns às turmas e elegíveis ao ator (interseção).
2. O ator seleciona as disciplinas e salva; o rascunho guarda a configuração.
3. Na publicação, a configuração é revalidada.

**Fluxos alternativos e de erro:**
- 1a. Texto livre `disciplina` enviado com DC1 ativo → proibido; `disciplina_ids` sem DC1 → proibido.
- 3a. Atribuição revogada ou turma arquivada antes da publicação → recusa.

**Critérios de aceite:**
- [ ] Legado de texto livre preservado, sem conversão automática.
- [ ] Revalidação nas três entradas de publicação.

**Situação:** Implementado — evidência: `ProvaController::opcoesDisciplinas`, `store`; `App\Services\CurriculoProva`, `OpcoesDisciplinaProva`; PR #186.

---

## UC-038-07 — Preparar simulados ENEM na homologação

**Ator:** operador técnico
**Permissão:** comando restrito à homologação
**Escopo:** escola de teste
**Pré-condições:** ambiente de homologação.
**Pós-condições:** 4 áreas, 13 disciplinas, professores de teste e provas adaptadas.
**Tela/rota:** `php artisan g360:preparar-simulados-homologacao [--aplicar]` (sem `--aplicar` só confere)

**Fluxos alternativos e de erro:**
- 1a. Fora do console, fora do ambiente `staging` com a base da homologação → "Operação exclusiva da homologação."
- 1b. DC1 ou espaços desligados → "Módulos indisponíveis."

**Critérios de aceite:**
- [ ] Não executa fora da homologação.

**Situação:** Implementado — evidência: `apps/api/app/Console/Commands/PrepararSimuladosHomologacao.php`; docs/156.
