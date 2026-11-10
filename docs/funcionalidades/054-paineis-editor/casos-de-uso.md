# 054 — Personalização e editor dos painéis — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** qualquer perfil com `turma.ver` e vínculo selecionado. Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Descreve o comportamento **atual** (na homologação, validado). Não há caso de uso correspondente no [docs/07](../../07-casos-de-uso.md).

---

## UC-054-01 — Consultar a organização salva de um painel

**Ator:** pessoa com `turma.ver`
**Permissão:** `turma.ver`
**Escopo:** o próprio vínculo; no painel `turma`, a turma precisa estar no escopo
**Pré-condições:** vínculo válido.
**Pós-condições:** nenhuma.
**Tela/rota:** web `paineis.preferencias.consultar`; API `GET v1/paineis/preferencias/{painel}` (cabeçalho `X-G360-Painel-Turma` no painel `turma`)

**Fluxo principal:**
1. Ao abrir Turmas ou uma Turma, a tela pede a organização do painel.
2. A API devolve a ordem, as larguras e as alturas desejadas — ou o padrão do perfil quando não há preferência —, já filtradas pelos componentes que a pessoa pode ver.
3. No modo editor, a resposta traz `ETag`; se nada mudou, a API responde 304.

**Fluxos alternativos e de erro:**
- 1a. Parâmetros na query: "Não envie parâmetros nesta consulta." (422).
- 1b. Seletor de turma ausente ou inválido no painel `turma`: "Informe uma turma válida para este painel."; seletor enviado no painel `turmas`: "Este painel não recebe seletor de turma."
- 1c. Sem vínculo válido: 403 "Selecione um vínculo válido."
- 2a. Preferência salva incompatível com o catálogo atual: volta ao padrão e sinaliza a indisponibilidade.

**Critérios de aceite:**
- [x] A preferência nunca revela componente não autorizado.
- [x] Respostas com `private, no-store`.

**Situação:** Implementado (homologação) — evidência: `PreferenciaPainelController::show`, `PreferenciasPainel::mostrar`; testes `PreferenciasPainelTest`, `PreferenciasPainelSegurancaWebTest`

---

## UC-054-02 — Mover e redimensionar os cards de um painel

**Ator:** pessoa com `turma.ver`
**Permissão:** `turma.ver`
**Escopo:** a própria organização daquele painel (e daquela turma)
**Pré-condições:** painel aberto no modo de organização.
**Pós-condições:** nova ordem e novas dimensões gravadas com versão incrementada.
**Tela/rota:** web `paineis.preferencias.salvar`; API `PUT v1/paineis/preferencias/{painel}`

**Fluxo principal:**
1. A pessoa entra em "Organizar", arrasta um card pela alça ou o redimensiona.
2. A largura é redistribuída entre os vizinhos da linha (mínimo de 3 colunas); cards fixados não se movem.
3. Ao salvar, a tela envia a configuração com a versão e a chave do catálogo; um toast confirma.

**Fluxos alternativos e de erro:**
- 3a. Outra aba salvou antes: 409 "A organização do painel mudou. Atualize antes de salvar."
- 3b. Catálogo alterado: 409 "O catálogo mudou. Consulte a disposição atual."
- 3c. Configuração fora do contrato: 422 "Configuração de painel inválida."
- 3d. Mesmo identificador de pedido com conteúdo diferente: 409 "Identificador já usado em outra configuração."
- 1a. Gesto cancelado antes de a tela ser desmontada: nada é gravado.

**Critérios de aceite:**
- [x] A seção fixa "resumo" não pode ser removida nem reordenada.
- [x] Gravações concorrentes não se sobrescrevem (teste com MariaDB).

**Situação:** Implementado (homologação) — evidência: `PreferenciasPainel::gravar`; testes `OrganizacaoPainelTest`, `EditorPainelConcorrenciaMariaDbTest`; PR #216, #218, #219

---

## UC-054-03 — Reordenar seções e compor painéis no editor hierárquico

**Ator:** pessoa com `turma.ver`
**Permissão:** `turma.ver`
**Escopo:** a própria composição
**Pré-condições:** editor aberto (opt-in pelo cabeçalho do editor).
**Pós-condições:** composição (painéis, seções e cards) e geometria gravadas.
**Tela/rota:** as mesmas do UC-054-02, com `X-G360-Painel-Editor: 3`

**Fluxo principal:**
1. A pessoa abre o editor e vê o quadro hierárquico: painéis, seções e cards.
2. Reordena seções e cards, cria seções ou painéis pessoais e escolhe os cards do catálogo permitido.
3. Ajusta a geometria e salva; a API devolve o novo `ETag`.

**Fluxos alternativos e de erro:**
- 3a. Os mesmos conflitos do UC-054-02.
- 3b. `ETag` em formato inválido: 422 no BFF.

**Critérios de aceite:**
- [x] Só cards do catálogo autorizado podem ser incluídos.
- [x] A composição salva sobrevive à troca de vínculo sem vazar para outro contexto.

**Situação:** Implementado (homologação, validado) — evidência: `BootstrapEditorPainel`, `ComposicaoPainel`; teste `EditorPainelTest`; PR #226, #227

---

## UC-054-04 — Receber o retorno das ações do editor

**Ator:** pessoa usando o editor
**Permissão:** `turma.ver`
**Escopo:** a própria sessão
**Pré-condições:** ação de salvar, cancelar ou falha.
**Pós-condições:** nenhuma.
**Tela/rota:** painéis de Turmas e da Turma

**Fluxo principal:**
1. Depois de cada ação, um toast flutuante informa o resultado sem deslocar o conteúdo.

**Critérios de aceite:**
- [x] Mensagens de sucesso, conflito e erro distintas.
- [ ] Conferência por texto em desktop e celular, temas claro e escuro, a cada mudança do editor.

**Situação:** Implementado (homologação) — evidência: PR #228 (FT1–FT3, docs/193)
