# 026 — Matrícula e enturmação (MT1) — Casos de uso

**Natureza:** evolução de existente (UC-01 a 03 documentados a partir do código; UC-04 a 06 propostos)
**Perfis envolvidos:** Coordenador, Diretor Escolar, Vice-diretor, Secretário Escolar (movimentação); perfis com `aluno.desativar`/`turma.desativar` (arquivamento). Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para o existente, descreve o comportamento **atual** e marca com ⚠ as diferenças em relação ao planejado.

---

## UC-026-01 — Mudar aluno de turma na mesma escola

**Ator:** Coordenador, Diretor Escolar, Vice-diretor ou Secretário Escolar
**Permissão:** `aluno.gerir` (e perfil na lista de `MovimentarAlunoTurma::autorizado`)
**Escopo:** escola
**Pré-condições:** aluno ativo; turma de destino ativa na mesma escola e no mesmo espaço; nenhuma leitura de cartão pendente ou em processamento no espaço.
**Pós-condições:** aluno na nova turma; cartões antigos com `turma_aplicacao_id` fixado; registro em `aluno_movimentacoes` com motivo cifrado, ator e vínculo.
**Tela/rota:** `/app/alunos/{id}/transferir` (página ou modal na turma) → `POST /app/alunos/{id}/transferencias-turma`; `POST /api/v1/alunos/{id}/transferencias-turma`.

**Fluxo principal:**
1. Na lista de alunos da turma, o ator escolhe "Transferir".
2. Seleciona a turma de destino (somente outras turmas ativas da mesma escola) e informa o motivo.
3. Revisa origem, destino e motivo; confirma.
4. A API valida, fixa o contexto histórico dos cartões, grava a movimentação e responde "Aluno transferido. Histórico preservado."

**Fluxos alternativos e de erro:**
- 2a. Não há outra turma ativa → aviso "Não há outra turma ativa e autorizada nesta escola."
- 4a. Turma do aluno mudou desde a abertura → `409 'A turma do aluno mudou. Atualize os dados antes de confirmar.'`
- 4b. Aluno arquivado → `422 'Ative o aluno antes de transferir.'`
- 4c. Mesma turma → `422 'Escolha outra turma.'`
- 4d. Escola ou espaço diferente → `422 'A transferência deve ocorrer entre turmas da mesma escola.'`
- 4e. Leituras em andamento → `409 'Há leituras de cartões em andamento. Aguarde a conclusão antes de transferir.'`
- 4f. Histórico inconsistente (nota/cartão de outro contexto) → `409 'Histórico precisa de conciliação. Nenhum dado foi movido.'`
- 4g. Repetição com a mesma chave e mesmo pedido → sem efeito novo; chave reutilizada com outro pedido → `409`.

**Regras de negócio:** docs/115 §7 (não mover notas antigas).

**Critérios de aceite:**
- [ ] Relatório da turma antiga continua contando a avaliação feita lá.
- [ ] Duplo envio não cria duas movimentações.

**Situação:** Implementado (homologação) — evidência: `apps/api/app/Services/MovimentarAlunoTurma.php`; PR #204.

⚠ A mudança também ocorre por `PUT /api/v1/alunos/{id}` com `turma_id`, sem motivo nem chave; ⚠ o rótulo "Transferir" colide com o vocabulário do docs/115 §2 ("Movimentar matrícula"). Resolver em MT1-B (subfase 2.2). ⚠ Não há matrícula nem intervalo de enturmação: a turma atual é um campo do aluno → MT1-A/B.

---

## UC-026-02 — Arquivar ou desarquivar aluno

**Ator:** perfil com `aluno.desativar` (ex.: Diretor Escolar, Coordenador, Secretário Escolar, professor autônomo no próprio espaço)
**Permissão:** `aluno.desativar`
**Escopo:** escola | espaço pessoal
**Pré-condições:** aluno no alcance; versão atual conhecida.
**Pós-condições:** `ativo` alterado; versão incrementada; registro em `cadastro_arquivamentos` com motivo cifrado. Nenhuma matrícula, prova, vínculo ou histórico é encerrado.
**Tela/rota:** ação na lista/ficha do aluno → `PUT /app/alunos/{id}/arquivamento`; `PUT /api/v1/alunos/{id}/arquivamento`.

**Fluxo principal:**
1. O ator escolhe arquivar (ou desarquivar) e, se quiser, informa motivo.
2. A API confere estado esperado e versão, grava e responde "Cadastro arquivado. Histórico preservado." ou "Cadastro desarquivado."

**Fluxos alternativos e de erro:**
- 2a. Campo fora de `arquivado, esperado_ativo, versao, chave, motivo` → `422`.
- 2b. Estado ou versão mudou → `409 'O estado do cadastro mudou. Atualize antes de confirmar.'`
- 2c. Chave já usada em outro pedido → `409 'Identificador já utilizado em outra solicitação.'`

**Critérios de aceite:**
- [ ] Repetir a mesma chave devolve o resultado anterior marcado como repetido.

**Situação:** Implementado (homologação) — evidência: `ArquivamentoCadastro`, `ArquivamentoCadastroController`; PR #207.

---

## UC-026-03 — Arquivar ou desarquivar turma

**Ator:** perfil com `turma.desativar`
**Permissão:** `turma.desativar`
**Escopo:** escola | espaço pessoal
**Pré-condições:** turma no alcance.
**Pós-condições:** como UC-026-02. Turma arquivada não recebe novas associações de prova (`exigirTurmasAtivas`: "Desarquive a turma antes de criar uma nova associação."), mas mantém as existentes.
**Tela/rota:** `PUT /app/turmas/{id}/arquivamento`; `PUT /api/v1/turmas/{id}/arquivamento`.

**Fluxo principal e erros:** iguais ao UC-026-02.

**Situação:** Implementado (homologação) — evidência: PR #207. ⚠ Rotas `toggle` antigas ainda existem → FN-036.

---

## UC-026-04 — Matricular aluno na escola por período

**Ator:** Secretário Escolar ou Direção (proposto)
**Permissão:** proposta a definir em MT1-A
**Escopo:** escola
**Pré-condições:** pessoa identificada (FN-016) ou provisória; período definido (FN-039).
**Pós-condições:** matrícula pessoa+escola+período; modalidades paralelas admitidas.

**Fluxo principal (proposto):** registrar a matrícula; o aluno passa a poder ser enturmado.

**Situação:** Não implementado — MT1-A (subfase 2.1).

---

## UC-026-05 — Enturmar com início e fim

**Ator:** Coordenador (proposto), Secretaria/Direção conforme permissão explícita
**Escopo:** escola

**Fluxo principal (proposto):**
1. Escolher a turma de destino e o instante de corte.
2. O sistema encerra a enturmação vigente e cria a nova atomicamente.

**Critérios de aceite:**
- [ ] Sem sobreposição indevida; professor apenas solicita/consulta, não movimenta.

**Situação:** Não implementado — MT1-B (subfase 2.2). Absorve o UC-026-01.

---

## UC-026-06 — Consultar relatórios pelo contexto histórico

**Ator:** perfis com `relatorio.turma`/`resultado.ver`
**Escopo:** turma | escola

**Fluxo principal (proposto):** o relatório da turma nova separa resultados anteriores (de outra turma) dos produzidos nela, sem contar a mesma nota duas vezes; PDFs e OMR usam o contexto da aplicação; correção tardia pertence ao contexto original.

**Situação:** Não implementado — MT1-C (subfase 2.3). Parcialmente coberto hoje por `cartoes.turma_aplicacao_id` (FN-022/FN-045).
